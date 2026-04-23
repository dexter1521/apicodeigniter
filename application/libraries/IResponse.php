<?php defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'interfaces/ResponseInterface.php';

class IResponse implements ResponseInterface
{
    private $status = null;
    private $success = false;
    private $response = null;
    private $messages = [];
    private $errors = [];
    private $timestamp = null;
    private $requestId = null;

    /**
     * Establece el estado de la respuesta.
     *
     * @param int $status The status code to set.
     * @return IResponse
     * @throws InvalidArgumentException
     */
    public function setStatus($status)
    {
        if (!is_int($status)) {
            throw new InvalidArgumentException('Status must be an integer');
        }
        $this->status = $status;
        return $this;
    }

    /**
     * Establece el estado de éxito de la respuesta.
     *
     * @param bool $success The success status to set.
     * @return IResponse
     */
    public function setSuccess($success)
    {
        $this->success = (bool) $success;
        return $this;
    }

    /**
     * Establece el valor de respuesta.
     *
     * @param mixed $response The response value to be set.
     * @return IResponse
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Establece el mensaje de la respuesta.
     *
     * @param string|array $message The message to be set.
     * @return IResponse
     */
    public function setMessage($message)
    {
        if ($message === null || $message === '') {
            return $this;
        }

        if (is_array($message)) {
            $this->messages = $message;
            return $this;
        }

        $this->messages = [$message];
        return $this;
    }

    /**
     * Establece un mensaje de validación para una clave específica.
     *
     * @param string $key The key to associate the validation message with.
     * @param string $message The validation message to set.
     * @return IResponse
     */
    public function setValidationMessage($key, $message)
    {
        $this->errors[$key] = $message;
        return $this;
    }

    /**
     * Añade un error.
     *
     * @param string $key
     * @param string $message
     * @return IResponse
     */
    public function addError($key, $message)
    {
        $this->errors[$key] = $message;
        return $this;
    }

    /**
     * Establece los errores.
     *
     * @param array $errors
     * @return IResponse
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Establece el timestamp de la respuesta.
     *
     * @param int|null $timestamp
     * @return IResponse
     */
    public function setTimestamp($timestamp = null)
    {
        $this->timestamp = $timestamp ?? time();
        return $this;
    }

    /**
     * Establece el ID de la solicitud.
     *
     * @param string $requestId
     * @return IResponse
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
        return $this;
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

    /**
     * Obtiene el estado.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Verifica si es exitosa.
     *
     * @return bool
     */
    public function isSuccess()
    {
        return (bool)$this->success;
    }

    /**
     * Obtiene los errores.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Convierte a array.
     *
     * @param bool $includeEmpty Incluir campos vacíos.
     * @return array
     */
    public function toArray($includeEmpty = true)
    {
        $array = [
            'status' => $this->status,
            'success' => $this->success,
        ];

        if ($includeEmpty || $this->response !== null) {
            $array['response'] = $this->response;
        }
        if ($includeEmpty || !empty($this->messages)) {
            $array['messages'] = $this->messages;
        }
        if ($includeEmpty || !empty($this->errors)) {
            $array['errors'] = $this->errors;
        }
        if ($this->timestamp) {
            $array['timestamp'] = $this->timestamp;
        }
        if ($this->requestId) {
            $array['request_id'] = $this->requestId;
        }

        return $array;
    }
}
