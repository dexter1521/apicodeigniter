<?php defined('BASEPATH') OR exit('No direct script access allowed');

#require (APPPATH.'libraries/REST_Controller.php');
#class Test extends REST_Controller

class Test extends MY_Controller{


    public function apiTest_post()
    {
        $this->response('Welcome to Tijuana', parent::HTTP_OK);
    }

    public function apiTestData_get()
    {
        $msg = $this->get('mensaje');
        if(isset($msg) && empty($msg))
        {
            #$this->response('No data!', parent::HTTP_UNAUTHORIZED);   //401
            $this->response('No data!', parent::HTTP_NOT_FOUND);   //404
        }else{
            $this->response($msg, parent::HTTP_OK);
        }
        
    }

    
}