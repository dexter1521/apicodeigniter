<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------
| JWT Secure Key
|--------------------------------------------------------------------------
*/
// Se lee la clave secreta desde una variable de entorno para mayor seguridad.
// El valor por defecto es solo para desarrollo si la variable no está definida.
$jwtKey = getenv('JWT_KEY');
if ($jwtKey === false || $jwtKey === '') {
	$jwtKey = $_ENV['JWT_KEY'] ?? 'ingDLMRuGe9UKHRNjs7cYckS2yul4lc3';
}
$config['jwt_key'] = $jwtKey;

/*
|-----------------------
| JWT Algorithm Type
|--------------------------------------------------------------------------
*/
$config['jwt_algorithm'] = 'HS256';

/*
|-----------------------
| JWT Issuer & Audience
|--------------------------------------------------------------------------
*/
$jwtIssuer = getenv('JWT_ISSUER');
if ($jwtIssuer === false || $jwtIssuer === '') {
	$jwtIssuer = $_ENV['JWT_ISSUER'] ?? getenv('BASE_URL') ?? $_ENV['BASE_URL'] ?? 'apicodeigniter-template';
}
$config['jwt_issuer'] = rtrim($jwtIssuer, '/');

$jwtAudience = getenv('JWT_AUDIENCE');
if ($jwtAudience === false || $jwtAudience === '') {
	$jwtAudience = $_ENV['JWT_AUDIENCE'] ?? $config['jwt_issuer'];
}
$config['jwt_audience'] = rtrim($jwtAudience, '/');

$leeway = getenv('JWT_LEEWAY');
if ($leeway === false || $leeway === '') {
	$leeway = $_ENV['JWT_LEEWAY'] ?? 60;
}
$config['jwt_leeway'] = (int) $leeway;

$nbfOffset = getenv('JWT_NBF_OFFSET');
if ($nbfOffset === false || $nbfOffset === '') {
	$nbfOffset = $_ENV['JWT_NBF_OFFSET'] ?? 0;
}
$config['jwt_nbf_offset'] = (int) $nbfOffset;

/*
|-----------------------
| Token Request Header Name
|--------------------------------------------------------------------------
*/
$config['token_header'] = 'token';

/*
|-----------------------
| Token Expire Time

| https://www.tools4noobs.com/online_tools/hh_mm_ss_to_seconds/
|--------------------------------------------------------------------------
| ( 1 Day ) : 60 * 60 * 24 = 86400
| ( 1 Hour ) : 60 * 60     = 3600
| ( 1 Minute ) : 60        = 60
*/
$tokenExpire = getenv('JWT_EXPIRE_TIME');
if ($tokenExpire === false || $tokenExpire === '') {
	$tokenExpire = $_ENV['JWT_EXPIRE_TIME'] ?? 3600;
}
if (!is_numeric($tokenExpire)) {
	log_message('error', 'JWT_EXPIRE_TIME debe ser numérico. Se aplicó el valor por defecto de 3600.');
	$tokenExpire = 3600;
}
$config['token_expire_time'] = (int) $tokenExpire; // 1 hora por defecto


/* End of file jwt.php */
/* Location: ./application/config/jwt.php */
