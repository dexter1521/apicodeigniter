<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Authorize_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
    }


    public function getUser($data)
    {
        $rsUser = $this->db->get_where('usuarios', array('usuario' => $data['usuario']));
        if ($rsUser->num_rows() == 1) {

            $result = $rsUser->row_array();

            if ($result['activo'] == 0) {

                return $msg = array('activo' => false, 'message' => 'El usuario al parecer se encuentra bloqueado');
            } else {
                $validatePassword = password_verify($data['password'], $result['contrasenia']);

                if ($validatePassword === true) {

                    return $msg = array('activo' => true, 'response' => $result);
                } else {

                    return $msg = array('activo' => false, 'message' => 'Verifique sus credenciales de acceso');
                }
            }
        } else {

            return $msg = array('activo' => false, 'message' => 'No existen registros con el usuario proporcionado');
        }
    }

    public function isUserEnabledById($userId)
    {
        $this->db->select('activo');
        $this->db->from('usuarios');
        $this->db->where('id_usuario', $userId);
        $query = $this->db->get();

        if ($query->num_rows() !== 1) {
            return false;
        }

        $row = $query->row_array();
        return isset($row['activo']) && (int) $row['activo'] === 1;
    }
}
