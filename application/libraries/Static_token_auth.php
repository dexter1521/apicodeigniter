<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Librería para validar tokens estáticos (API keys) emitidos a aplicaciones externas.
 */
class Static_token_auth
{
    private $ci;
    private $table = 'api_tokens';

    public function __construct()
    {
        $this->ci = get_instance();
        $this->ci->load->database();
    }

    /**
     * Valida un token estático activo y actualiza su estado de "último uso".
     *
     * @param string $token
     * @return object|false
     */
    public function validate(string $token)
    {
        if (empty($token)) {
            return false;
        }

        $tokenData = $this->fetchTokenRow($token);
        if ($tokenData === null) {
            log_message('warning', 'Token estático no encontrado o no activo');
            return false;
        }

        if ($this->isExpired($tokenData)) {
            log_message('warning', 'Token estático expirado para app: ' . ($tokenData->app_name ?? 'unknown'));
            return false;
        }

        if (!$this->enforceRateLimit($tokenData)) {
            return false;
        }

        $this->updateLastUsed($tokenData->id);

        return (object) [
            'app_name' => $tokenData->app_name,
            'auth_type' => 'static_token',
            'token_id' => $tokenData->id,
            'scopes' => $tokenData->scopes ?? null,
            'metadata' => [
                'rate_limit' => $tokenData->rate_limit ?? null,
                'expires_at' => $tokenData->expires_at ?? null,
            ],
        ];
    }

    private function fetchTokenRow(string $token)
    {
        $tokenHash = hash('sha256', $token);

        $this->ci->db->group_start()
            ->where('token', $token)
            ->or_where('token_hash', $tokenHash)
            ->group_end();
        $this->ci->db->where('is_active', 1);

        $query = $this->ci->db->get($this->table);
        if ($query->num_rows() === 0) {
            return null;
        }

        return $query->row();
    }

    private function isExpired($tokenData)
    {
        return isset($tokenData->expires_at)
            && $tokenData->expires_at
            && strtotime($tokenData->expires_at) < time();
    }

    private function enforceRateLimit($tokenData)
    {
        if (!isset($tokenData->app_name)) {
            return true;
        }

        $this->ci->load->library('RateLimit');
        $limit = (int) ($tokenData->rate_limit ?? 1000);
        $endpoint = $this->ci->uri->uri_string();

        if (!$this->ci->ratelimit->checkLimit($tokenData->app_name, $endpoint, $limit, 3600)) {
            log_message('warning', 'Rate limit exceeded para app: ' . $tokenData->app_name);
            return false;
        }

        return true;
    }

    private function updateLastUsed(int $tokenId)
    {
        return $this->ci->db->where('id', $tokenId)
            ->update($this->table, ['last_used_at' => date('Y-m-d H:i:s')]);
    }
}
