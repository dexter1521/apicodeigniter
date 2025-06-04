<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once(APPPATH . 'models/General_model.php');

class Clientes_model extends General_model
{

    function __construct()
    {
        parent::__construct();
    }


	public function getClients()
	{
		$clientes = $this->query_data('usuarios', [], ['nombre', 'usuario']);
		return $clientes;
	}



}
