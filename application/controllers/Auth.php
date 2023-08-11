<?php defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_Controller
{

  function __construct()
  {
    parent::__construct();
    $this->load->model('Authorize_model');
  }

  public function login_post()
  {

    $message = array('status' => null, 'success' => false, 'messages' => array());

    $config = array(
      array(
        'field' => 'usuario',
        'label' => 'usuario',
        'rules' => 'required|trim|min_length[3]|max_length[10]'
      ),
      array(
        'field' => 'contrasenia',
        'label' => 'password',
        'rules' => 'required|trim|min_length[8]|max_length[16]'
      )
    );

    #$this->form_validation->set_error_delimiters('<span class="alert alert-danger">','</span>');
    $this->form_validation->set_rules($config);

    if ($this->form_validation->run() === FALSE) {

      $message['status'] = 400;
      $message['success'] = false;
      foreach ($_POST as $key => $value) {
        $message['messages'][$key] = form_error($key);
      }
      $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
    } else {

      $params = array(
        'usuario' => $this->post('usuario'),
        'password' => $this->post('contrasenia')
      );

      $returnData = $this->Authorize_model->getUser($params);

      if ($returnData['bandera'] == false) {
        # code...
        $message = array('status' => 400, 'success' => $returnData['bandera'], 'messages' => $returnData['message']);
        $this->response($message, REST_Controller::HTTP_BAD_REQUEST);
      } else {
        
        $date = date("Y-m-d h:i:s", time());

        $tokenData = array(
          'usuario'       => $returnData['response']['USUARIO'],
          'nombre'   => $returnData['response']['nombre'],
          'supervisor'   => $returnData['response']['SUPERVISOR'],
          'vendedor'   => $returnData['response']['VENDEDOR'],
          'cobrador'   => $returnData['response']['COBRADOR'],
          'vendersinexistencia' => $returnData['response']['existencia'],
          'uuid'   => uniqid(),
          'timestamp' => $date,
          'in_session' => true
        );

        $message = array('status' => 200, 'success' => true, 'token' => AUTHORIZATION::generateToken($tokenData));

        $this->response($message, REST_Controller::HTTP_OK);
        
      }
    }
  } //termina login


  public function logout_post()
  {

    $vars = array('id', 'usuario', 'nombre', 'perfil', 'uuid', 'in_session');
    $this->session->unset_userdata($vars);
    $this->session->sess_destroy();
    redirect(base_url('Auth'));
  }

  public function tokenRetrieve_post()
  {
    $message = array('status' => null, 'success' => false, 'messages' => '');

    $headers = $this->input->request_headers(); 
    #hacemos debug a los headers del navegador
    #$headers = $this->input->get_request_header('token');
    #$this->set_response($headers, REST_Controller::HTTP_OK);

    if (isset($headers['token'])) {
      #TODO: Change 'token_timeout' in application\config\jwt.php
      $decodedToken = AUTHORIZATION::validateTimestamp($headers['token']);
      
      if ($decodedToken != false) {
        $this->set_response($decodedToken, REST_Controller::HTTP_OK);
      }else{
        $this->set_response($decodedToken, REST_Controller::HTTP_OK);
      }

    } else {
      $message = array('status' => 401, 'success' => false, 'messages' => 'Token failed');
      $this->set_response($message, REST_Controller::HTTP_UNAUTHORIZED);
    }
  }

  
}
