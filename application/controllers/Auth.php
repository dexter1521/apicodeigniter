<?php defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_Controller
{

	protected $ci;

	function __construct()
	{
		parent::__construct();
		$this->load->model('Authorize_model');
	}

	public function login_post()
	{

		$message = array('status' => null, 'success' => false, 'messages' => array());

		$config = array(
			array(
				'field' => 'usuario',
				'label' => 'usuario',
				'rules' => 'required|trim|min_length[3]|max_length[10]'
			),
			array(
				'field' => 'contrasenia',
				'label' => 'password',
				'rules' => 'required|trim|min_length[8]|max_length[16]'
			)
		);

		$this->form_validation->set_error_delimiters('', '');
		$this->form_validation->set_rules($config);

		if ($this->form_validation->run() === FALSE) {

			$message['status'] = 200;
			$message['success'] = false;
			foreach ($_POST as $key => $value) {
				$message['messages'][$key] = form_error($key);
			}
			return $this->response($message, $message['status']);
		} else {

			$params = array(
				'usuario' => trim($this->post('usuario')),
				'password' => trim($this->post('contrasenia'))
			);

			$resultado = $this->Authorize_model->getUser($params);

			if (!$resultado['activo']) {
				$message = array('status' => 400, 'success' => false, 'messages' => $resultado['message']);
				return $this->response($message, $message['status']);
			}

			$date = time();
			$stampado = date("Y-m-d H:i:s", time());
			$ci = &get_instance();
			$authorization = new authorization($ci);

			$expirationTimeInSeconds = $ci->config->item('token_expire_time');
			if (!intval($expirationTimeInSeconds)) {
				log_message('error', 'token_expire_time no es un valor numérico válido.');
				$expirationTimeInSeconds = 43200; // Valor por defecto 12hrs.
			}

			$tokenData = array(
				'usuario'       => $resultado['response']['usuario'],
				'nombre'   => $resultado['response']['nombre'],
				'uuid'   => uniqid(),
				'timestamp' => $stampado,
				'expiration' => $date + $expirationTimeInSeconds,
				'expiration_date' => date("Y-m-d H:i:s", $date + $expirationTimeInSeconds),
				'ip' => $CI->input->ip_address()
			);
			$token = authorization::generateToken($tokenData);

			if (authorization::validateToken($token)) {
				$message = array('status' => 200, 'success' => true, 'token' => $token);
			} else {
				$message = array('status' => 500, 'success' => false, 'messages' => 'Error generating token');
			}

			$this->response($message, $message['status']);
		}
	} //termina login

	public function tokenRetrieve_post()
	{
		$message = array('status' => 400, 'success' => false, 'messages' => '');

		$headers = $this->input->get_request_header('Authorization');
		if ($headers !== false && !empty($headers)) {
			$decodedToken = AUTHORIZATION::validateToken($headers);
			if ($decodedToken !== false) {
				// Token válido, puedes devolverlo al cliente si es necesario
				// Obtener la fecha de expiración en formato legible
				$expiration = date("Y-m-d H:i:s", $decodedToken->expiration);
				// Agregar la fecha de expiración al array de respuesta
				$decodedToken->expiration_date = $expiration;
				$this->set_response($decodedToken, 200);
				return;
			}
		}

		// El token no se proporcionó o no es válido
		$message = array('status' => 401, 'success' => false, 'messages' => 'Token no enviado o inválido');
		$this->set_response($message, 401);
	}


	public function tokenRetrieve_get()
	{
		$token = $this->input->get_request_header('Authorization');

		if (!$token) {
			// No se proporcionó ningún token
			$this->handleUnauthorizedAccess("No se proporcionó ningún token", parent::HTTP_UNAUTHORIZED);
			return;
		}

		// Verificar si el token es válido
		$decodedToken = AUTHORIZATION::validateToken($token);

		if (!$decodedToken) {
			// El token proporcionado no es válido o ha expirado
			$this->handleUnauthorizedAccess("El token proporcionado no es válido o ha expirado", parent::HTTP_UNAUTHORIZED);
			return;
		}

		// Token válido, puedes devolverlo al cliente si es necesario
		$this->set_response($decodedToken, 200);
	}
}
