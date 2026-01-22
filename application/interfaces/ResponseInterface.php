<?php

interface ResponseInterface
{
	public function setStatus($status);
	public function setSuccess($success);
	public function setResponse($response);
	public function setMessage($message);
	public function setValidationMessage($key, $message);
	public function addError($key, $message);
	public function setErrors(array $errors);
	public function getStatus();
	public function isSuccess();
	public function getResponse();
	public function getMessages();
	public function getErrors();
	public function toArray();
}
