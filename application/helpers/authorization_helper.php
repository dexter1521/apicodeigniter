<?php

class AUTHORIZATION
{
    protected $CI; // Instance of CodeIgniter (injected through constructor)

    public function __construct($CI)
    {
        $this->CI = $CI;
    }

    public function validateTimestamp($token)
    {
        $date = date("Y-m-d h:i:s", time());
        $token = $this->validateToken($token);

        if ($token != false && (strtotime($date) - strtotime($token->timestamp) < ($this->CI->config->item('token_expire_time') * 60))) {
            return $token;
        }
        return false;
    }

    public function validateToken($token)
    {
        try {
            return JWT::decode($token, $this->CI->config->item('jwt_key'));
        } catch (Exception $e) {
            // Handle decode exception (e.g., return false or throw a specific exception)
            return false;
        }
    }

    public function generateToken($data)
    {
        return JWT::encode($data, $this->CI->config->item('jwt_key'));
    }
}