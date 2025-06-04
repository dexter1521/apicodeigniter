<?php

class authorization
{
	public static function validateTimestamp($token)
	{
		$date = time();
		$CI = &get_instance();
		$token = self::validateToken($token);

		if (!$token) {
			return false;
		}

		// Verifica la fecha de expiración correctamente
		if ($date < strtotime($token->expiration_date)) {
			return $token;
		}

		// Si el token ha expirado, devuelve `false`
		return false;
	}

	public static function validateToken($token)
	{
		$CI = &get_instance();
		try {
			// Decodifica el token utilizando el algoritmo HS256
			$decodedToken = JWT::decode($token, $CI->config->item('jwt_key'));
			return $decodedToken;
		} catch (Exception $e) {
			log_message('error', 'Error al validar el token: ' . $e->getMessage());
			return false;
		}
	}

	public static function generateToken($data)
	{
		$CI = &get_instance();
		return JWT::encode($data, $CI->config->item('jwt_key'));
	}
}
