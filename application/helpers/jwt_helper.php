<?php

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;

if (!function_exists('jwt_encode')) {
	function jwt_encode(array $payload, string $key, string $algorithm = 'HS256')
	{
		return FirebaseJWT::encode($payload, $key, $algorithm);
	}
}

if (!function_exists('jwt_decode')) {
	function jwt_decode(string $jwt, string $key, string $algorithm = 'HS256')
	{
		return FirebaseJWT::decode($jwt, new Key($key, $algorithm));
	}
}
