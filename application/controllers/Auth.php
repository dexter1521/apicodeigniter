<?php defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_Controller
{

	protected $ci;

	function __construct()
	{
		parent::__construct();
		$this->load->model('Authorize_model');
	}

	public function index_post()
	{
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(
				array(
					'status' => 200,
					'success' => true,
					'messages' => 'Api Connected Successfully post'
				)
			));
	}

	public function index_get()
	{
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(
				array(
					'status' => 200,
					'success' => true,
					'messages' => 'Api Connected Successfully get'
				)
			));
	}

	public function login_post()
	{

		$message = array();

		$config = array(
			array(
				'field' => 'usuario',
				'label' => 'usuario',
				'rules' => 'required|trim|min_length[3]|max_length[50]|valid_email'
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

			$returnData = $this->Authorize_model->getUser($params);

			if (!$returnData['activo']) {
				$message = array('status' => 400, 'success' => false, 'messages' => $returnData['message']);
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
				'id'            => $returnData['response']->id_usuario,
				'usuario'       => $returnData['response']->usuario,
				'nombre'        => $returnData['response']->nombre,
				'correo'        => $returnData['response']->correo,
				'perfil'        => $returnData['response']->id_perfil,
				'sucursal'      => $returnData['response']->id_sucursal,
				'uuid'          => uniqid(),
				'timestamp'     => $stampado,
				'expiration'    => $date + $expirationTimeInSeconds,
				'expiration_date' => date("Y-m-d H:i:s", $date + $expirationTimeInSeconds),
				'in_session'    => true,
				'admin'      => $returnData['response']->admin,
				//'permisos'      => $returnData['permisos'] //array de permisos del usuario para  aplicar al front
			);

			$token = $authorization->generateToken($tokenData);

			if ($authorization->validateToken($token)) {
				$message = array(
					'status' => 200,
					'success' => true,
					'Authorization' => 'Bearer ' . $token
				);
			} else {
				$message = array(
					'status' => 500,
					'success' => false,
					'messages' => 'Error generating token'
				);
			}

			$this->response($message, $message['status']);
		}
	} //termina login

	public function logout_post()
	{
		$headers = $this->input->request_headers();

		if (array_key_exists('Authorization', $headers) && !empty($headers['Authorization'])) {
			$token = $headers['Authorization'];
			$decodedToken = Authorization::validateToken($token);

			if ($decodedToken != false) {
				// Aquí puedes agregar la lógica para cerrar la sesión del usuario
				// $this->Authorize_model->cerrar_login_model($decodedToken);

				$this->response('adios vaquero!', 200);
			} else {
				$this->response('Error de autenticación', 401);
			}
		} else {
			$this->response('Token no proporcionado', 401);
		}
	}

	public function tokenRetrieve_post()
	{
		$message = array('status' => null, 'success' => false, 'messages' => '');

		#hacemos debug a los headers del navegador
		$headers = $this->input->request_headers();

		#$headers = $this->input->get_request_header('Authorization');


		if (array_key_exists('Authorization', $headers) && !empty($headers['Authorization'])) {
			$CI = &get_instance(); // Aquí obtienes la instancia de CodeIgniter
			$authorization = new AUTHORIZATION($CI); // Creas una instancia de la clase AUTHORIZATION
			$decodedToken = $authorization->validateTimestamp($headers['Authorization']);

			if ($decodedToken != false) {
				$this->set_response($decodedToken, $message['status']);
			} else {

				$message['messages'] = 'Token inválido';
				$this->set_response($message, REST_Controller::HTTP_UNAUTHORIZED);
			}
		} else {
			$message['messages'] = 'Token ausente';
			$this->set_response($message, REST_Controller::HTTP_UNAUTHORIZED);
		}
	}
}
