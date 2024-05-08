<?php
class TokenNotProvidedException extends Exception{}
class InvalidTokenException extends Exception{}

require APPPATH . 'libraries/REST_Controller.php';

class MY_Controller extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('jwt');
        $this->load->helper('Authorization');
    }

    protected function handleUnauthorizedAccess($errorMessage, $errorCode)
    {
        $this->output->set_status_header($errorCode);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(
                array(
                    'status' => false,
                    'message' => $errorMessage
                )
            ));
    }

    protected function validateToken($authorizationHeader)
    {
        if (empty($authorizationHeader)) {

            #throw new Exception("Token no proporcionado");
            throw new TokenNotProvidedException("Token no proporcionado");
        }

        $decodedToken = Authorization::validateTimestamp($authorizationHeader);

        if ($decodedToken == false) {

            #throw new Exception("Token inválido o ha expirado");
            throw new InvalidTokenException("Token inválido o ha expirado");
        }

        return $decodedToken;
    }
}
