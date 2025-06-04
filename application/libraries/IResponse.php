<?php defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'interfaces/ResponseInterface.php';

class IResponse implements ResponseInterface
{
	private $status = null;
	private $success = false;
	private $response = '';
	private $messages = [];

	/**
	 * Establece el estado de la respuesta.
	 *
	 * @param int $status The status code to set.
	 * @return void
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * Establece el estado de éxito de la respuesta.
	 *
	 * @param bool $success The success status to set.
	 * @return void
	 */
	public function setSuccess($success)
	{
		$this->success = $success;
	}

	/**
	 * Establece el valor de respuesta.
	 *
	 * @param mixed $response The response value to be set.
	 * @return void
	 */
	public function setResponse($response)
	{
		$this->response = $response;
	}

	/**
	 * Establece el mensaje de la respuesta.
	 *
	 * @param string $message The message to be set.
	 * @return void
	 */
	public function setMessage($message)
	{
		//$this->messages[] = $message;
		$this->messages = $message;
	}

	/**
	 * Establece un mensaje de validación para una clave específica.
	 *
	 * @param string $key The key to associate the validation message with.
	 * @param string $message The validation message to set.
	 * @return void
	 */
	public function setValidationMessage($key, $message)
	{
		$this->messages[$key] = $message;
	}

	/**
	 * Obtiene la respuesta de la API.
	 *
	 * @return mixed The response.
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * Obtiene los mensajes asociados con la respuesta.
	 *
	 * @return array The messages associated with the response.
	 */
	public function getMessages()
	{
		return $this->messages;
	}

	public function toArray()
	{
		// Verifica si hay exactamente un mensaje y lo devuelve directamente.
		// Si hay exactamente un mensaje, no se usa reset() para mantener la estructura de clave-valor.
		// Asegura que $this->messages es un array antes de contar sus elementos.
		$messages = is_array($this->messages) && count($this->messages) === 1 ? $this->messages : $this->messages;

		return [
			'status' => $this->status,
			'success' => $this->success,
			'response' => $this->response,
			'messages' => $messages,
		];
	}
}
