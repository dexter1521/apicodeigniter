<?php defined('BASEPATH') or exit('No direct script access allowed');

class Clientes extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('General_model');
    }

    public function getClients_get()
    {
        try {
            $authorizationHeader = $this->input->get_request_header('Authorization');
            $decodedToken = $this->validateToken($authorizationHeader);

            $clientes = $this->General_model->query_data('usuarios', [], ['nombre', 'usuario']);
            $this->response($clientes, parent::HTTP_OK);

        } catch (TokenNotProvidedException $e) {
            $this->handleUnauthorizedAccess($e->getMessage(), parent::HTTP_UNAUTHORIZED);
        } catch (InvalidTokenException $e) {
            $this->handleUnauthorizedAccess($e->getMessage(), parent::HTTP_UNAUTHORIZED);
        } catch (Exception $e) {
            $this->handleUnauthorizedAccess($e->getMessage(), parent::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
