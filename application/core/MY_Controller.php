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
    protected $ci;

    public function __construct()
    {
        parent::__construct();
        $this->ci = &get_instance();
        $this->load->helper('jwt');
        $this->load->helper('Authorization');
    }

    protected function handleUnauthorizedAccess($errorMessage)
    {
        $this->output->set_status_header(401);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(
                array(
                    'status' => false,
                    'message' => $errorMessage
                )
            ));
    }

    public function validateToken($authorizationHeader)
    {
        $authorization = new AUTHORIZATION($this->ci); // Crear una instancia de AUTHORIZATION
        $decodedToken = $this->tokenRetrieve($authorizationHeader, $authorization);
        if ($decodedToken == false) {
            throw new Exception('Token inválido');
        }
        return $decodedToken;
    }

    public function tokenRetrieve($authorizationHeader, $authorization)
    {
        if (!empty($authorizationHeader)) {
            $decodedToken = $authorization->validateTimestamp($authorizationHeader); // Llamar al método no estático
            if ($decodedToken != false) {
                return $decodedToken;
            }
        }
        return false;
    }
}
