<?php

class authorization
{
    public static function validateTimestamp($token)
    {
        $date = date("Y-m-d H:i:s", time());
        $CI = &get_instance();
        $token = self::validateToken($token);

        if (!$token) {
            return false;
        }

        if (strtotime($date) < $token->expiration) {
            return $token;
        }
    }

    public static function validateToken($token)
    {
        $CI = &get_instance();
        try {
            $decodedToken = JWT::decode($token, $CI->config->item('jwt_key'));
            return $decodedToken;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function generateToken($data)
    {
        $CI = &get_instance();
        return JWT::encode($data, $CI->config->item('jwt_key'));
    }
}
