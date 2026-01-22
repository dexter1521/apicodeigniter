<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
 * Eduardo Marvil
 * emtv2126@gmail.com
 * 14/02/2023
 * Descripcion: Controlador principal de toda la aplicacion
 * */

// Excepciones personalizadas para manejo de errores de token
class TokenNotProvidedException extends Exception {}
class InvalidTokenException extends Exception {}

require APPPATH . 'libraries/REST_Controller.php';

/**
 * Class MY_Controller
 *
 * @property CI_Session $session
 * @property CI_Loader $load
 * @property CI_Config $config
 * @property CI_DB_driver $db
 * @property CI_DB_query_builder $db
 * @property CI_Input $input
 * @property CI_Output $output
 */

class MY_Controller extends REST_Controller
{
	// Definir constantes para mensajes de error
	private const ERROR_TOKEN_NOT_PROVIDED = "Token no proporcionado o formato inválido en la petición";
	private const ERROR_TOKEN_EXPIRED = "Token inválido o ha expirado, por favor inicie sesión nuevamente";
	private const ERROR_INVALID_STATIC_TOKEN = "Token estático inválido o no existe en la base de datos";
	private const ERROR_INVALID_STATIC_TOKEN_EXPIRED = "Token estático inválido o ha expirado, por favor inicie sesión nuevamente";
	private const ERROR_INVALID_STATIC_TOKEN_NOT_ACTIVE = "Token estático no activo o no existe en la base de datos";
	private const ERROR_INVALID_STATIC_TOKEN_NOT_FOUND = "Token estático no encontrado en la base de datos";
	private const ERROR_USER_NOT_ACTIVE = "Usuario no autorizado o inactivo";

	protected $apiResponse;
	protected $authenticatedUser = null;
	private $isConnected = true;

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(['jwt', 'Authorization']);
		$this->_checkDatabaseConnection(); // Verificar conexión a la base de datos al iniciar el controlador
	}

	private function _checkDatabaseConnection()
	{
		try {
			$this->load->database();
			$this->db->query('SELECT 1'); // Operación simple para verificar la conexión
		} catch (Exception $e) {
			$this->isConnected = false; // Si hay un error, asumir que no hay conexión
		}
	}


	public function index_post()
	{
		if (!$this->isConnected) {
			$this->response([
				'status' => FALSE,
				'message' => 'No se pudo conectar al servidor. Por favor, intente más tarde.'
			], REST_Controller::HTTP_SERVICE_UNAVAILABLE); // Asegúrate de usar el código de estado HTTP correcto
			return;
		}
	}

	public function index_get()
	{
		if (!$this->isConnected) {
			$this->response([
				'status' => FALSE,
				'message' => 'No se pudo conectar al servidor. Por favor, intente más tarde.'
			], REST_Controller::HTTP_SERVICE_UNAVAILABLE); // Asegúrate de usar el código de estado HTTP correcto
			return;
		}
	}

	protected function handleUnauthorizedAccess($errorMessage, $errorCode)
	{
		return $this->sendErrorResponse($errorMessage, $errorCode);
	}

	protected function requireAuthentication()
	{
		if ($this->authenticatedUser !== null) {
			return true;
		}

		$tokenData = $this->tokenRetrieve();
		if ($tokenData === null) {
			return false;
		}

		$this->authenticatedUser = $tokenData;
		return true;
	}

	protected function getAuthenticatedUser()
	{
		if ($this->authenticatedUser === null) {
			$this->requireAuthentication();
		}

		return $this->authenticatedUser;
	}

	protected function sendSuccessResponse($data = null, $message = 'Operación exitosa', $status = REST_Controller::HTTP_OK)
	{
		$response = $this->buildApiResponse(true, $status, $message, $data);
		return $this->response($response, $status);
	}

	protected function sendErrorResponse($message, $status = REST_Controller::HTTP_BAD_REQUEST, $details = null)
	{
		$payload = null;
		$errors = [];

		if (is_array($details)) {
			$errors = $details;
		} elseif ($details !== null) {
			$payload = $details;
		}

		$response = $this->buildApiResponse(false, $status, $message, $payload, $errors);
		return $this->response($response, $status);
	}

	protected function sendValidationResponse(array $errors, $message = 'Datos inválidos', $status = REST_Controller::HTTP_UNPROCESSABLE_ENTITY)
	{
		return $this->sendErrorResponse($message, $status, $errors);
	}

	private function buildApiResponse($success, $status, $message = null, $payload = null, array $errors = [])
	{
		$response = new IResponse();
		$response->setStatus($status);
		$response->setSuccess($success);

		if ($payload !== null) {
			$response->setResponse($payload);
		}

		if ($message !== null) {
			$response->setMessage($message);
		}

		if (!empty($errors)) {
			$response->setErrors($errors);
		}

		return $response->toArray();
	}

	protected function validateToken($tokenValue)
	{
		if (empty($tokenValue)) {
			throw new TokenNotProvidedException(self::ERROR_TOKEN_NOT_PROVIDED);
		}

		$decodedToken = Authorization::validateTimestamp($tokenValue);
		if ($decodedToken === false) {
			throw new InvalidTokenException(self::ERROR_TOKEN_EXPIRED);
		}

		$this->load->model('Auth_model');
		$tokenUserId = $decodedToken->id_usuario ?? null;
		$enabledRow = $tokenUserId ? $this->Auth_model->UserEnabled($tokenUserId) : null;
		if (empty($enabledRow) || (int)($enabledRow['enabled'] ?? 0) !== 1) {
			throw new InvalidTokenException(self::ERROR_USER_NOT_ACTIVE);
		}

		return $decodedToken;
	}

	public function tokenRetrieve($headers = null)
	{
		$normalizedHeaders = $this->normalizeHeaders($headers ?? $this->input->request_headers());

		try {
			if (is_array($normalizedHeaders) && isset($normalizedHeaders['x-api-key'])) {
				return $this->validateStaticToken($normalizedHeaders['x-api-key']);
			}

			$token = $this->extractTokenFromHeaders($normalizedHeaders);
			return $this->validateToken($token);
		} catch (TokenNotProvidedException $e) {
			$this->respondTokenFailure($e->getMessage(), REST_Controller::HTTP_UNAUTHORIZED);
		} catch (InvalidTokenException $e) {
			$this->respondTokenFailure($e->getMessage(), REST_Controller::HTTP_FORBIDDEN);
		}

		return null;
	}

	private function extractTokenFromHeaders($headers)
	{
		if (!empty($headers)) {
			if (is_array($headers)) {
				if (isset($headers['authorization'])) {
					$authHeader = $headers['authorization'];
					if (strpos($authHeader, 'Bearer ') === 0) {
						return substr($authHeader, 7);
					}
					return $authHeader;
				}
				if (isset($headers['token'])) {
					return $headers['token'];
				}
			} elseif (is_string($headers)) {
				if (strpos($headers, 'Bearer ') === 0) {
					return substr($headers, 7);
				}
				return $headers;
			}
		}

		throw new TokenNotProvidedException(self::ERROR_TOKEN_NOT_PROVIDED);
	}

	/**
	 * Valida token estático emitido a aplicaciones externas contra la base de datos.
	 */
	protected function validateStaticToken($token)
	{
		if (empty($token)) {
			throw new TokenNotProvidedException(self::ERROR_TOKEN_NOT_PROVIDED);
		}

		$this->db->where('token', $token);
		$this->db->where('is_active', 1);
		$query = $this->db->get('api_tokens');

		if ($query->num_rows() === 0) {
			throw new InvalidTokenException(self::ERROR_INVALID_STATIC_TOKEN);
		}

		$token_data = $query->row();

		// Opcional: Verificar fecha de expiración si existe
		if (
			isset($token_data->expires_at) && $token_data->expires_at
			&& strtotime($token_data->expires_at) < time()
		) {
			throw new InvalidTokenException(self::ERROR_INVALID_STATIC_TOKEN_EXPIRED);
		}

		// Actualizar última fecha de uso
		$this->db->where('id', $token_data->id)
			->update('api_tokens', ['last_used_at' => date('Y-m-d H:i:s')]);

		return (object)[
			'app_name' => $token_data->app_name,
			'auth_type' => 'static_token'
		];
	}

	private function normalizeHeaders($headers)
	{
		if (!is_array($headers)) {
			return $headers;
		}

		$normalized = [];
		foreach ($headers as $key => $value) {
			$normalized[strtolower($key)] = $value;
		}

		return $normalized;
	}

	private function respondTokenFailure($message, $statusCode)
	{
		return $this->sendErrorResponse($message, $statusCode, ['path' => $this->uri->uri_string()]);
	}
}
