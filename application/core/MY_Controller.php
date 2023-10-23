<?php
/*
 * Eduardo Marvil
 * eduardo.dritec@gmail.com
 * 14/02/2023
 * Descripcion: Controlador principal de toda la aplicacion
 * */

require APPPATH . 'libraries/REST_Controller.php';

class MY_Controller extends REST_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('jwt');
        $this->load->helper('Authorization');
    }

    public function tokenRetrieve($headers)
    {
        if (array_key_exists('Authorization', $headers) && !empty($headers['Authorization'])) {
            $decodedToken = Authorization::validateTimestamp($headers['Authorization']);
            if ($decodedToken != false) {
                //$datos = array(0 => $decodedToken);
                return $decodedToken;
            }
        }
        return false;
    }
}