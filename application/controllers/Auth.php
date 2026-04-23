<?php defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_Controller
{
	const LOGIN_RATE_LIMIT = 5; // máximo 5 intentos
	const LOGIN_RATE_WINDOW = 900; // ventana de 15 minutos

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Authorize_model');
		$this->load->library(['RateLimit', 'Token_service']);
	}

	public function login_post()
	{
		try {
			// Rate limiting
			$ipAddress = $this->input->ip_address();
			if (!$this->ratelimit->checkLimit($ipAddress, '/auth/login', self::LOGIN_RATE_LIMIT, self::LOGIN_RATE_WINDOW)) {
				log_message('warning', "Rate limit exceeded for login attempt from IP: {$ipAddress}");
				$info = $this->ratelimit->getInfo($ipAddress, '/auth/login', self::LOGIN_RATE_LIMIT);
				return $this->sendErrorResponse(
					'Demasiados intentos de login. Intenta nuevamente en ' . ceil(($info['reset_at'] - time()) / 60) . ' minutos',
					REST_Controller::HTTP_TOO_MANY_REQUESTS,
					['retry_after' => $info['reset_at'] - time()]
				);
			}

			$payload = $this->parseJsonBody();
			$this->form_validation->reset_validation();
			$this->form_validation->set_data($payload);
			$this->form_validation->set_rules([
				[
					'field' => 'usuario',
					'label' => 'Usuario',
					'rules' => 'required|trim|min_length[3]|max_length[120]|valid_email'
				],
				[
					'field' => 'contrasenia',
					'label' => 'Contraseña',
					'rules' => 'required|trim|min_length[8]|max_length[255]'
				],
			]);

			if ($this->form_validation->run() === false) {
				return $this->sendValidationResponse($this->form_validation->error_array(), 'Credenciales inválidas');
			}

			$params = [
				'usuario' => strtolower($payload['usuario']),
				'password' => $payload['contrasenia'],
			];

			$userResult = $this->Authorize_model->getUser($params);
			if (!($userResult['activo'] ?? false)) {
				return $this->sendErrorResponse($userResult['message'] ?? 'Credenciales incorrectas', REST_Controller::HTTP_UNAUTHORIZED);
			}

			$user = (array) ($userResult['response'] ?? []);
			if (empty($user)) {
				return $this->sendErrorResponse('Usuario no encontrado', REST_Controller::HTTP_UNAUTHORIZED);
			}

			$expiresIn = (int) $this->config->item('token_expire_time');
			$claims = [
				'sub' => $user['id_usuario'] ?? null,
				'email' => $user['correo'] ?? null,
				'name' => $user['nombre'] ?? null,
				'usuario' => $user['usuario'] ?? null,
				'perfil' => $user['id_perfil'] ?? null,
				'sucursal' => $user['id_sucursal'] ?? null,
				'admin' => (bool) ($user['admin'] ?? false),
			];

			$token = authorization::generateToken($claims, ['expiration' => $expiresIn]);
			$refreshToken = $this->token_service->issueRefreshToken($claims);
			
			log_message('info', 'Login exitoso para usuario: ' . ($user['usuario'] ?? 'unknown'));
			
			$successPayload = [
				'token_type' => 'Bearer',
				'access_token' => $token,
				'expires_in' => $expiresIn,
				'issued_at' => time(),
				'refresh_token' => $refreshToken,
			];

			return $this->sendSuccessResponse($successPayload, 'Autenticación exitosa');
		} catch (Exception $exception) {
			log_message('error', 'Error en login: ' . $exception->getMessage());
			return $this->sendErrorResponse('No se pudo completar la autenticación', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	public function logout_post()
	{
		if (!$this->requireAuthentication()) {
			return;
		}

		try {
			$authHeader = $this->input->request_headers()['Authorization'] ?? null;
			if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
				$token = substr($authHeader, 7);
				$this->token_service->revokeAccessToken($token, 'User logout');
			}

			log_message('info', 'Usuario ' . ($this->authenticatedUser->sub ?? 'unknown') . ' cerró sesión');
			return $this->sendSuccessResponse(['revocado' => true], 'Sesión cerrada exitosamente');
		} catch (Exception $exception) {
			log_message('error', 'Error en logout: ' . $exception->getMessage());
			return $this->sendErrorResponse('No se pudo cerrar la sesión', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	public function refresh_post()
	{
		try {
			$payload = $this->parseJsonBody();
			$refreshToken = $payload['refresh_token'] ?? null;

			if (!$refreshToken) {
				return $this->sendErrorResponse('Refresh token requerido', REST_Controller::HTTP_BAD_REQUEST);
			}

			// Obtener el token expirado del header para extraer user_id
			$authHeader = $this->input->request_headers()['Authorization'] ?? null;
			if (!$authHeader || strpos($authHeader, 'Bearer ') !== 0) {
				return $this->sendErrorResponse('Access token requerido en header', REST_Controller::HTTP_BAD_REQUEST);
			}

			$expiredToken = substr($authHeader, 7);
			$decoded = authorization::validateToken($expiredToken);

			// Si el token no es válido, intentar decodificar sin validación
			if ($decoded === false) {
				$decoded = $this->token_service->decodeTokenWithoutValidation($expiredToken);
				if ($decoded === false) {
					return $this->sendErrorResponse('Access token inválido', REST_Controller::HTTP_UNAUTHORIZED);
				}
			}

			$userId = $decoded->sub ?? null;
			if (!$userId) {
				return $this->sendErrorResponse('Invalid token claims', REST_Controller::HTTP_BAD_REQUEST);
			}

			// Validar refresh token
			if (!$this->token_service->validateRefreshToken($refreshToken, $userId)) {
				log_message('warning', "Refresh token inválido para user ID: {$userId}");
				return $this->sendErrorResponse('Refresh token inválido o expirado', REST_Controller::HTTP_UNAUTHORIZED);
			}

			// Generar nuevo access token
			$expiresIn = (int) $this->config->item('token_expire_time');
			$newAccessToken = authorization::generateToken((array) $decoded, ['expiration' => $expiresIn]);

			log_message('info', "Token renovado para user ID: {$userId}");

			$response = [
				'token_type' => 'Bearer',
				'access_token' => $newAccessToken,
				'expires_in' => $expiresIn,
				'issued_at' => time(),
			];

			return $this->sendSuccessResponse($response, 'Token renovado');
		} catch (Exception $exception) {
			log_message('error', 'Error en refresh: ' . $exception->getMessage());
			return $this->sendErrorResponse('No se pudo refrescar el token', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	private function parseJsonBody(): array
	{
		$rawBody = $this->input->raw_input_stream;
		if (empty($rawBody)) {
			return $this->input->post(NULL, true) ?: [];
		}

		$decoded = json_decode($rawBody, true);
		return is_array($decoded) ? $decoded : [];
	}
}
