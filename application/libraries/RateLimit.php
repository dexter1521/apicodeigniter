<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Librería RateLimit para Control de Velocidad de Acceso
 * 
 * Maneja rate limiting para:
 * - Login (prevención de brute force)
 * - Endpoints específicos
 * - Tokens estáticos (API keys)
 * 
 * @package RateLimit
 * @author Senior API Developer
 */
class RateLimit
{
	private $ci;
	private $enabled = true;
	private $table = 'rate_limit_logs';

	public function __construct()
	{
		$this->ci = get_instance();
		$this->ci->load->database();
	}

	/**
	 * Verifica si se ha excedido el límite de rate para un identificador.
	 * 
	 * @param string $identifier IP, user_id o token
	 * @param string $endpoint Endpoint accedido (e.g., '/auth/login')
	 * @param int $maxAttempts Máximo de intentos permitidos
	 * @param int $windowSeconds Ventana de tiempo en segundos (e.g., 3600 = 1 hora)
	 * @return bool TRUE si está dentro del límite, FALSE si se excedió
	 */
	public function checkLimit($identifier, $endpoint, $maxAttempts = 10, $windowSeconds = 3600)
	{
		if (!$this->enabled || empty($identifier) || empty($endpoint)) {
			return true;
		}

		$resetAt = date('Y-m-d H:i:s', time() + $windowSeconds);
		$currentTime = date('Y-m-d H:i:s');

		// Obtener o crear registro
		$this->ci->db->where('identifier', $identifier)
					 ->where('endpoint', $endpoint)
					 ->where('reset_at', '>', $currentTime);
		$query = $this->ci->db->get($this->table);

		if ($query->num_rows() > 0) {
			$record = $query->row();
			if ($record->request_count >= $maxAttempts) {
				log_message('warning', "Rate limit exceeded for {$identifier} on {$endpoint}");
				return false;
			}

			// Incrementar contador
			$this->ci->db->where('id', $record->id)
						 ->update($this->table, [
							 'request_count' => $record->request_count + 1,
							 'updated_at' => $currentTime
						 ]);
		} else {
			// Crear nuevo registro
			$this->ci->db->insert($this->table, [
				'identifier' => $identifier,
				'endpoint' => $endpoint,
				'request_count' => 1,
				'reset_at' => $resetAt,
				'created_at' => $currentTime
			]);
		}

		return true;
	}

	/**
	 * Obtiene información de rate limit para un identificador.
	 * 
	 * @param string $identifier
	 * @param string $endpoint
	 * @return array ['current' => int, 'limit' => int, 'reset_at' => timestamp]
	 */
	public function getInfo($identifier, $endpoint, $maxAttempts = 10)
	{
		$currentTime = date('Y-m-d H:i:s');

		$this->ci->db->where('identifier', $identifier)
					 ->where('endpoint', $endpoint)
					 ->where('reset_at', '>', $currentTime);
		$query = $this->ci->db->get($this->table);

		if ($query->num_rows() > 0) {
			$record = $query->row();
			return [
				'current' => $record->request_count,
				'limit' => $maxAttempts,
				'reset_at' => strtotime($record->reset_at),
				'remaining' => max(0, $maxAttempts - $record->request_count)
			];
		}

		return [
			'current' => 0,
			'limit' => $maxAttempts,
			'reset_at' => time() + 3600,
			'remaining' => $maxAttempts
		];
	}

	/**
	 * Limpia registros expirados.
	 * 
	 * @return int Registros eliminados
	 */
	public function cleanup()
	{
		$currentTime = date('Y-m-d H:i:s');
		$this->ci->db->where('reset_at', '<', $currentTime);
		$this->ci->db->delete($this->table);
		return $this->ci->db->affected_rows();
	}

	/**
	 * Habilita o deshabilita el rate limiting.
	 * 
	 * @param bool $enabled
	 */
	public function setEnabled($enabled)
	{
		$this->enabled = (bool) $enabled;
	}

	/**
	 * Verifica si está habilitado.
	 * 
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}
}
