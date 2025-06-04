<?php

interface ResponseInterface
{
    public function setStatus($status);
    public function setSuccess($success);
    public function setResponse($response);
    public function setMessage($message);
    public function setValidationMessage($key, $message);
    public function toArray();
}
