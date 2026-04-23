<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Librería para validación de tokens JWT y verificación de usuario activo.
 */
class Jwt_auth
{
    private $ci;

    public function __construct()
    {
        $this->ci = get_instance();
        $this->ci->load->helper(['jwt', 'Authorization']);
        $this->ci->load->model('Authorize_model');
    }

    /**
     * Valida un token JWT firmado y no revocado, y comprueba que el usuario exista y esté activo.
     *
     * @param string $token
     * @return object|false
     */
    public function validate(string $token)
    {
        if (empty($token)) {
            return false;
        }

        $decoded = authorization::validateToken($token);
        if ($decoded === false) {
            return false;
        }

        $userId = $decoded->sub ?? null;
        if (empty($userId) || !$this->ci->Authorize_model->isUserEnabledById($userId)) {
            log_message('warning', 'Usuario inactivo o no encontrado al validar token JWT');
            return false;
        }

        return $decoded;
    }
}
