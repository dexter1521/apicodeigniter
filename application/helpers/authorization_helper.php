<?php

class AUTHORIZATION
{
    public static function validateTimestamp($token)
    {
        $date = date("Y-m-d H:i:s", time());
        $CI = &get_instance();
        $token = self::validateToken($token);
        if (strtotime($date) < $token->expiration) {
            return $token;
        }

        return false;
    }

    public static function validateToken($token)
    {
        $CI = &get_instance();
        return JWT::decode($token, $CI->config->item('jwt_key'));
    }

    public static function generateToken($data)
    {
        $CI = &get_instance();
        return JWT::encode($data, $CI->config->item('jwt_key'));
    }
}