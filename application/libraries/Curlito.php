<?php

class Curlito
{
    private $SISTEMA = 0; // 0 = SISTEMA DE PRUEBAS, 1 = SISTEMA DE PRODUCCION
	private $IP = ""; // IP del servidor
	private $TOKEN = ""; // Token de acceso

	private function generar_url($ruta)
	{

		return $this->IP . "/" . $ruta;
	}

	private function generar_parametros($parametros)
	{

		$default = "id_sistema=" . $this->SISTEMA . "&api_key=" . $this->TOKEN;
		return $default . "&" . $parametros;
	}

    /*
	*-------------------------------
	* Comienza bloque de funciones curl para
	* formar las peticiones al servidor
	*-------------------------------
	*/

	public function curl($uri, $parametros)
	{
		try {
			$handler = curl_init();

			curl_setopt($handler, CURLOPT_URL, $uri);
			curl_setopt($handler, CURLOPT_POST, true);
			curl_setopt($handler, CURLOPT_POSTFIELDS, $parametros);
			curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);

			$respuesta = curl_exec($handler);
			curl_close($handler);

			return $respuesta;
		} catch (\Exception $e) {
			return $e;
		}
	}


	public function curl_fields_array($uri, $parametros)
	{

		$handler = curl_init();

		curl_setopt($handler, CURLOPT_URL, $uri);
		curl_setopt($handler, CURLOPT_POST, true);
		curl_setopt($handler, CURLOPT_POSTFIELDS, http_build_query($parametros));
		curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);

		$respuesta = curl_exec($handler);

		curl_close($handler);

		return $respuesta;
	}


	public function curl_get($uri)
	{

		$handler = curl_init();

		curl_setopt($handler, CURLOPT_URL, $uri);
		curl_setopt($handler, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);

		$respuesta = curl_exec($handler);

		curl_close($handler);

		return $respuesta;
	}

	
}