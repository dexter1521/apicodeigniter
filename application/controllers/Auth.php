<?php defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('Authorize_model');
	}

	public function login_post()
	{
		try {
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
			$successPayload = [
				'token_type' => 'Bearer',
				'access_token' => $token,
				'expires_in' => $expiresIn,
				'issued_at' => time(),
				'refresh_token' => $this->issueRefreshToken($user),
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

		// Hook para revocar tokens en almacenamiento persistente.
		return $this->sendSuccessResponse(['revocado' => true], 'Sesión cerrada. Implementa la revocación según tu necesidad.');
	}

	public function refresh_post()
	{
		try {
			$refreshToken = $this->extractRefreshToken();
			if (!$refreshToken) {
				return $this->sendErrorResponse('Refresh token requerido', REST_Controller::HTTP_BAD_REQUEST);
			}

			// Valida el refresh token en tu almacenamiento propio.
			$claims = $this->rebuildClaimsFromRefresh($refreshToken);
			if ($claims === null) {
				return $this->sendErrorResponse('Refresh token inválido', REST_Controller::HTTP_UNAUTHORIZED);
			}

			$expiresIn = (int) $this->config->item('token_expire_time');
			$newToken = authorization::generateToken($claims, ['expiration' => $expiresIn]);
			$response = [
				'token_type' => 'Bearer',
				'access_token' => $newToken,
				'expires_in' => $expiresIn,
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

	private function issueRefreshToken(array $user): string
	{
		try {
			if (function_exists('random_bytes')) {
				$entropy = random_bytes(32);
			} elseif (function_exists('openssl_random_pseudo_bytes')) {
				$entropy = openssl_random_pseudo_bytes(32);
			} else {
				$entropy = uniqid('', true);
			}
		} catch (Exception $exception) {
			log_message('debug', 'No se pudo generar entropía criptográfica: ' . $exception->getMessage());
			$entropy = uniqid('', true);
		}

		$base = ($user['usuario'] ?? '') . '|' . microtime(true);
		$hash = hash('sha256', $base . $entropy, true);
		return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
	}

	private function extractRefreshToken(): ?string
	{
		$payload = $this->parseJsonBody();
		if (!empty($payload['refresh_token'])) {
			return $payload['refresh_token'];
		}

		$headers = $this->input->request_headers();
		return $headers['X-Refresh-Token'] ?? null;
	}

	private function rebuildClaimsFromRefresh(string $refreshToken): ?array
	{
		// Este método es un placeholder. Agrega aquí la lógica para validar y reconstruir claims.
		return null;
	}
}
