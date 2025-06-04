<?php defined('BASEPATH') or exit('No direct script access allowed');

class Clientes extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Clientes_model', 'clientes');
	}

	public function index_post()
	{
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(
				array(
					'status' => true,
					'messages' => 'Api Connected Successfully'
				)
			));
	}

	public function index_get()
	{
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(
				array(
					'status' => true,
					'messages' => 'Api Connected Successfully'
				)
			));
	}



	public function getClients_get()
	{
		try {
			// Validar el token de autorización
			$this->_getToken();

			$data = $this->getClients();
			//$this->response($response, parent::HTTP_OK);

			if (is_array($data) && count($data) > 0) {
				$this->apiResponse->setStatus(200);
				$this->apiResponse->setSuccess(true);
				$this->apiResponse->setResponse($data);
				$this->set_response($this->apiResponse->getResponse(), REST_Controller::HTTP_OK);
			} else {
				$this->apiResponse->setStatus(400);
				$this->apiResponse->setMessage('Lo sentimos, la búsqueda no arroja resultados.');
				$this->set_response($this->apiResponse->toArray(), REST_Controller::HTTP_BAD_REQUEST);
			}




		} catch (TokenNotProvidedException $e) {
			$this->handleUnauthorizedAccess($e->getMessage(), parent::HTTP_UNAUTHORIZED);
		} catch (InvalidTokenException $e) {
			$this->handleUnauthorizedAccess($e->getMessage(), parent::HTTP_UNAUTHORIZED);
		} catch (Exception $e) {
			$this->handleUnauthorizedAccess($e->getMessage(), parent::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	private function _getToken()
	{
		$token = $this->input->get_request_header('X-API-KEY') ?: $this->input->get_request_header('Authorization');
		return $this->tokenRetrieve($token);
	}
}
