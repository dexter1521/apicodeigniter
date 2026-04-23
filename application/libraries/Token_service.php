<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Servicio para la gestión del ciclo de vida de tokens JWT y refresh tokens.
 */
class Token_service
{
    private $ci;
    private $refreshTable = 'refresh_tokens';
    private $blacklistTable = 'token_blacklist';

    public function __construct()
    {
        $this->ci = get_instance();
        $this->ci->load->database();
        $this->ci->load->helper('Authorization');
    }

    public function issueRefreshToken(array $userClaims, int $durationSeconds = 2592000)
    {
        $userId = $userClaims['sub'] ?? null;
        if (empty($userId)) {
            log_message('error', 'No se puede emitir refresh token sin user_id');
            return null;
        }

        try {
            $tokenString = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $tokenString);
            $expiresAt = date('Y-m-d H:i:s', time() + $durationSeconds);

            $data = [
                'user_id' => $userId,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'ip_address' => $this->ci->input->ip_address(),
                'user_agent' => substr($this->ci->input->user_agent(), 0, 255),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->ci->db->insert($this->refreshTable, $data);
            log_message('info', "Refresh token emitido para user ID: {$userId}");
            return $tokenString;
        } catch (Exception $exception) {
            log_message('error', 'Error emitiendo refresh token: ' . $exception->getMessage());
            return null;
        }
    }

    public function validateRefreshToken(string $refreshToken, int $userId)
    {
        if (empty($refreshToken) || empty($userId)) {
            return false;
        }

        try {
            $tokenHash = hash('sha256', $refreshToken);
            $this->ci->db->where('user_id', $userId)
                ->where('token_hash', $tokenHash)
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->where('revoked_at', null);

            $query = $this->ci->db->get($this->refreshTable);
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

    public function revokeRefreshToken(string $refreshToken, int $userId)
    {
        if (empty($refreshToken) || empty($userId)) {
            return false;
        }

        try {
            $tokenHash = hash('sha256', $refreshToken);
            $this->ci->db->where('user_id', $userId)
                ->where('token_hash', $tokenHash)
                ->update($this->refreshTable, ['revoked_at' => date('Y-m-d H:i:s')]);

            log_message('info', "Refresh token revocado para user ID: {$userId}");
            return true;
        } catch (Exception $exception) {
            log_message('error', 'Error revocando refresh token: ' . $exception->getMessage());
            return false;
        }
    }

    public function revokeAccessToken(string $accessToken, string $reason = 'User logout')
    {
        if (empty($accessToken)) {
            return false;
        }

        return authorization::revokeToken($accessToken, $reason);
    }

    public function decodeTokenWithoutValidation(string $token)
    {
        return authorization::decodeTokenWithoutValidation($token);
    }
}
