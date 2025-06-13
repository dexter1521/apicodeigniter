<?php defined('BASEPATH') or exit('No direct script access allowed');

// Excepciones personalizadas para manejo de errores de token
class TokenNotProvidedException extends Exception {}
class InvalidTokenException extends Exception {}

require APPPATH . 'libraries/REST_Controller.php';

class MY_Controller extends REST_Controller
{
	// Definir constantes para mensajes de error
	private const ERROR_TOKEN_NOT_PROVIDED = "Token no proporcionado o formato inválido en la petición";
	private const ERROR_INVALID_TOKEN = "Token inválido: ";
	private const ERROR_TOKEN_EXPIRED = "Token inválido o ha expirado, por favor inicie sesión nuevamente";
	private const ERROR_INVALID_STATIC_TOKEN = "Token estático inválido o no existe en la base de datos";
	private const ERROR_INVALID_STATIC_TOKEN_EXPIRED = "Token estático inválido o ha expirado, por favor inicie sesión nuevamente";
	private const ERROR_INVALID_STATIC_TOKEN_NOT_ACTIVE = "Token estático no activo o no existe en la base de datos";
	private const ERROR_INVALID_STATIC_TOKEN_NOT_FOUND = "Token estático no encontrado en la base de datos";

	// Importar la clase IResponse para manejar respuestas de API
	protected $apiResponse;
	private $isConnected = true;

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(['jwt', 'Authorization']);
		$this->apiResponse = new IResponse();
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
		$this->output->set_status_header($errorCode);
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(
				array(
					'status' => false,
					'message' => $errorMessage
				),
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
			));
	}

	protected function validateToken($authorizationHeader)
	{
		if (empty($authorizationHeader)) {
			throw new TokenNotProvidedException(self::ERROR_TOKEN_NOT_PROVIDED);
		}

		$decodedToken = Authorization::validateTimestamp($authorizationHeader);
		if ($decodedToken === false) {
			throw new InvalidTokenException(self::ERROR_TOKEN_EXPIRED);
		}

		return $decodedToken;
	}

	public function tokenRetrieve($headers)
	{
		$headers = $this->input->request_headers();

		// 1. Primero intentamos con token estático (X-API-KEY)
		if (isset($headers['X-API-KEY'])) {
			return $this->validateStaticToken($headers['X-API-KEY']);
		}
		// 2. Si no hay token estático, intentamos con el token JWT
		try {
			$token = $this->extractTokenFromHeaders($headers);
			return $this->validateToken($token);
		} catch (TokenNotProvidedException | InvalidTokenException $e) {
			$this->response(self::ERROR_INVALID_TOKEN . $e->getMessage(), parent::HTTP_CONFLICT);
			exit();
		}
	}

	private function extractTokenFromHeaders($headers)
	{
		if (!empty($headers)) {
			if (is_array($headers)) {
				// Si el token viene en 'Authorization'
				if (isset($headers['Authorization'])) {
					$authHeader = $headers['Authorization'];
					if (strpos($authHeader, 'Bearer ') === 0) {
						return substr($authHeader, 7);
					}
					return $authHeader;
				}
				// Si el token viene en 'token'
				if (isset($headers['token'])) {
					return $headers['token'];
				}
			} elseif (is_string($headers)) {
				// Si solo se pasa el token como string directamente
				if (strpos($headers, 'Bearer ') === 0) {
					return substr($headers, 7);
				}
				return $headers;
			}
		}

		throw new TokenNotProvidedException(self::ERROR_TOKEN_NOT_PROVIDED);
	}

	/**
	 * Valida token estático contra la base de datos
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
}
