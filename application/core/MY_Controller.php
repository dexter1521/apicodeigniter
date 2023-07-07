<?php
/*
 * Eduardo Marvil
 * eduardo.dritec@gmail.com
 * 14/02/2023
 * Descripcion: Controlador principal de toda la aplicacion
 * */

require APPPATH . 'libraries/REST_Controller.php';

class MY_Controller extends REST_Controller
{
    public function __construct()
	{
		parent::__construct();
	}
}