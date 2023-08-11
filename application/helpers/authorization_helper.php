<?php

class AUTHORIZATION
{
    public static function validateTimestamp($token)
    {
        $date = date("Y-m-d h:i:s", time()); 
        $CI = &get_instance();
        $token = self::validateToken($token);
            
        if ($token != false && (strtotime($date) - strtotime($token->timestamp) < ($CI->config->item('token_expire_time') * 60))) {
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
