<?php

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class authorization
{
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

        return FirebaseJWT::encode($tokenClaims, $key, $algorithm);
    }

    /**
     * Valida un token JWT verificando firma, vencimiento y claim nbf.
     */
    public static function validateToken(string $token)
    {
        $ci = self::getCI();
        $key = self::getConfigItem($ci, 'jwt_key');
        $algorithm = self::getConfigItem($ci, 'jwt_algorithm') ?: 'HS256';
        $leeway = (int) self::getConfigItem($ci, 'jwt_leeway');

        FirebaseJWT::$leeway = max(0, $leeway);

        try {
            $decoded = FirebaseJWT::decode($token, new Key($key, $algorithm));
            return $decoded;
        } catch (ExpiredException $expiredException) {
            log_message('error', 'Token expirado: ' . $expiredException->getMessage());
        } catch (BeforeValidException $beforeValidException) {
            log_message('error', 'Token usado antes de tiempo válido: ' . $beforeValidException->getMessage());
        } catch (SignatureInvalidException $signatureInvalidException) {
            log_message('error', 'Firma de token inválida: ' . $signatureInvalidException->getMessage());
        } catch (Exception $exception) {
            log_message('error', 'Token inválido: ' . $exception->getMessage());
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
            log_message('debug', 'Fallo al generar ID criptográfico, usando uniqid: ' . $exception->getMessage());
            return md5(uniqid('', true));
        }
    }
}
