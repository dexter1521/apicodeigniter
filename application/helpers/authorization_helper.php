<?php

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class authorization
{
    private static $blacklistTable = 'token_blacklist';
    private static $refreshTable = 'refresh_tokens';

    /**
     * Genera un token JWT con claims estándares y personalizados.
     */
    public static function generateToken(array $claims, array $options = [])
    {
        $ci = self::getCI();
        $key = self::getConfigItem($ci, 'jwt_key');
        $algorithm = self::getConfigItem($ci, 'jwt_algorithm') ?: 'HS256';
        $expiration = isset($options['expiration']) ? (int)$options['expiration'] : (int) self::getConfigItem($ci, 'token_expire_time');
        $issuer = $options['iss'] ?? self::getConfigItem($ci, 'jwt_issuer');
        $audience = $options['aud'] ?? self::getConfigItem($ci, 'jwt_audience');
        $notBeforeOffset = isset($options['nbf_offset']) ? (int) $options['nbf_offset'] : (int) self::getConfigItem($ci, 'jwt_nbf_offset');
        $now = time();

        if ($expiration <= 0) {
            $expiration = 3600;
        }

        $tokenClaims = $claims;
        $tokenClaims['iss'] = $tokenClaims['iss'] ?? $issuer;
        $tokenClaims['aud'] = $tokenClaims['aud'] ?? $audience;
        $tokenClaims['iat'] = $tokenClaims['iat'] ?? $now;
        $tokenClaims['nbf'] = $tokenClaims['nbf'] ?? $now + $notBeforeOffset;
        $tokenClaims['exp'] = $tokenClaims['exp'] ?? $now + $expiration;
        $tokenClaims['jti'] = $tokenClaims['jti'] ?? self::generateTokenId();

        $token = FirebaseJWT::encode($tokenClaims, $key, $algorithm);
        log_message('info', 'Token generado con JTI: ' . ($tokenClaims['jti'] ?? 'N/A'));
        return $token;
    }

    /**
     * Revoca un token agregándolo a la blacklist.
     */
    public static function revokeToken(string $token, string $reason = 'User logout')
    {
        try {
            $decoded = self::validateToken($token);
            if ($decoded === false) {
                log_message('warning', 'Intento de revocar token inválido');
                return false;
            }

            $ci = self::getCI();
            $tokenHash = hash('sha256', $token);
            $jti = $decoded->jti ?? null;
            $userId = $decoded->sub ?? null;
            $expiresAt = date('Y-m-d H:i:s', $decoded->exp ?? time());

            if (!$jti || !$userId) {
                log_message('error', 'Token sin JTI o sub claim');
                return false;
            }

            $data = [
                'token_jti' => $jti,
                'token_hash' => $tokenHash,
                'user_id' => $userId,
                'reason' => $reason,
                'expires_at' => $expiresAt
            ];

            $ci->db->insert(self::$blacklistTable, $data);
            log_message('info', "Token revocado para user ID: {$userId}");
            return true;
        } catch (Exception $exception) {
            log_message('error', 'Error al revocar token: ' . $exception->getMessage());
            return false;
        }
    }

    /**
     * Verifica si un token está en la blacklist.
     */
    public static function isTokenBlacklisted(string $jti)
    {
        if (empty($jti)) {
            return false;
        }

        try {
            $ci = self::getCI();
            $ci->db->where('token_jti', $jti);
            $query = $ci->db->get(self::$blacklistTable);
            return $query->num_rows() > 0;
        } catch (Exception $exception) {
            log_message('error', 'Error al verificar blacklist: ' . $exception->getMessage());
            return false;
        }
    }

    /**
     * Valida un token JWT verificando firma, vencimiento, claim nbf y blacklist.
     */
    public static function validateToken(string $token)
    {
        if (empty($token)) {
            log_message('warning', 'Token vacío proporcionado para validación');
            return false;
        }

        $ci = self::getCI();
        $key = self::getConfigItem($ci, 'jwt_key');
        $algorithm = self::getConfigItem($ci, 'jwt_algorithm') ?: 'HS256';
        $leeway = (int) self::getConfigItem($ci, 'jwt_leeway');

        FirebaseJWT::$leeway = max(0, $leeway);

        try {
            $decoded = FirebaseJWT::decode($token, new Key($key, $algorithm));

            // Verificar blacklist
            if (isset($decoded->jti) && self::isTokenBlacklisted($decoded->jti)) {
                log_message('warning', 'Token revocado (en blacklist) - JTI: ' . $decoded->jti);
                return false;
            }

            // Validar claims obligatorios
            if (!isset($decoded->sub) || !isset($decoded->jti)) {
                log_message('error', 'Token sin claims obligatorios (sub, jti)');
                return false;
            }

            log_message('debug', 'Token validado exitosamente - User: ' . ($decoded->sub ?? 'N/A'));
            return $decoded;
        } catch (ExpiredException $expiredException) {
            log_message('warning', 'Token expirado');
        } catch (BeforeValidException $beforeValidException) {
            log_message('warning', 'Token usado antes de su activación (nbf)');
        } catch (SignatureInvalidException $signatureInvalidException) {
            log_message('warning', 'Firma de token inválida');
        } catch (Exception $exception) {
            log_message('error', 'Error validando token: ' . substr($exception->getMessage(), 0, 100));
        }

        return false;
    }

    /**
     * Alias usado por código existente para validar expiración.
     */
    public static function validateTimestamp(string $token)
    {
        return self::validateToken($token);
    }

    private static function getCI()
    {
        return get_instance();
    }

    private static function getConfigItem($ci, string $item)
    {
        return $ci->config->item($item);
    }

    private static function generateTokenId()
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Exception $exception) {
            log_message('debug', 'Fallo al generar ID criptográfico, usando alternativa');
            return md5(uniqid('', true));
        }
    }

    /**
     * Crea y almacena un refresh token.
     */
    public static function issueRefreshToken(array $userClaims, array $options = [])
    {
        try {
            $ci = self::getCI();
            $userId = $userClaims['sub'] ?? null;
            if (!$userId) {
                log_message('error', 'Cannot issue refresh token without user ID');
                return null;
            }

            // Generar token hash
            $tokenString = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $tokenString);
            $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 3600)); // 30 días

            $data = [
                'user_id' => $userId,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'ip_address' => $ci->input->ip_address(),
                'user_agent' => substr($ci->input->user_agent(), 0, 255)
            ];

            $ci->db->insert(self::$refreshTable, $data);
            log_message('info', "Refresh token emitido para user ID: {$userId}");
            return $tokenString; // Retornar el token sin hash
        } catch (Exception $exception) {
            log_message('error', 'Error emitiendo refresh token: ' . $exception->getMessage());
            return null;
        }
    }

    /**
     * Valida un refresh token.
     */
    public static function validateRefreshToken(string $tokenString, int $userId)
    {
        try {
            $ci = self::getCI();
            $tokenHash = hash('sha256', $tokenString);

            $ci->db->where('user_id', $userId)
                    ->where('token_hash', $tokenHash)
                    ->where('expires_at', '>', date('Y-m-d H:i:s'))
                    ->where('revoked_at', null);
            $query = $ci->db->get(self::$refreshTable);

            if ($query->num_rows() === 0) {
                log_message('warning', "Refresh token inválido/expirado para user ID: {$userId}");
                return false;
            }

            log_message('debug', "Refresh token válido para user ID: {$userId}");
            return true;
        } catch (Exception $exception) {
            log_message('error', 'Error validando refresh token: ' . $exception->getMessage());
            return false;
        }
    }

    /**
     * Revoca un refresh token.
     */
    public static function revokeRefreshToken(string $tokenString, int $userId)
    {
        try {
            $ci = self::getCI();
            $tokenHash = hash('sha256', $tokenString);

            $ci->db->where('user_id', $userId)
                    ->where('token_hash', $tokenHash)
                    ->update(self::$refreshTable, ['revoked_at' => date('Y-m-d H:i:s')]);

            log_message('info', "Refresh token revocado para user ID: {$userId}");
            return true;
        } catch (Exception $exception) {
            log_message('error', 'Error revocando refresh token: ' . $exception->getMessage());
            return false;
        }
    }

    /**
     * Decodifica un token sin validar firma ni expiración (solo para refresh).
     * SOLO USAR PARA EXTRAER CLAIMS DE TOKENS EXPIRADOS EN ENDPOINTS DE REFRESH.
     */
    public static function decodeTokenWithoutValidation(string $token)
    {
        try {
            // Decodificar sin verificar
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                log_message('error', 'Formato de token inválido');
                return false;
            }

            $payload = $parts[1];
            // Decodificar payload base64
            $decoded = base64_decode($payload, true);
            if ($decoded === false) {
                log_message('error', 'No se pudo decodificar payload base64');
                return false;
            }

            $json = json_decode($decoded);
            if ($json === null) {
                log_message('error', 'Payload no es JSON válido');
                return false;
            }

            log_message('debug', 'Token decodificado sin validación - User: ' . ($json->sub ?? 'N/A'));
            return $json;
        } catch (Exception $exception) {
            log_message('error', 'Error decodificando token: ' . $exception->getMessage());
            return false;
        }
    }
}
