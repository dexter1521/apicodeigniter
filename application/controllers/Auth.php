<?php defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_Controller
{

  protected $ci;

  function __construct()
  {
    parent::__construct($this->ci);
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

    $this->form_validation->set_error_delimiters('', '');
    $this->form_validation->set_rules($config);

    if ($this->form_validation->run() === FALSE) {

      $message['status'] = 200;
      $message['success'] = false;
      foreach ($_POST as $key => $value) {
        $message['messages'][$key] = form_error($key);
      }
      $this->response($message, $message['status']);
    } else {

      $params = array(
        'usuario' => $this->post('usuario'),
        'password' => $this->post('contrasenia')
      );

      $returnData = $this->Authorize_model->getUser($params);

      if ($returnData['bandera'] == false) {
        # code...
        $message = array('status' => 200, 'success' => $returnData['bandera'], 'messages' => $returnData['message']);
        $this->response($message, $message['status']);
      } else {

        $date = date("Y-m-d H:i:s", time());
        $CI = &get_instance(); // Aquí obtienes la instancia de CodeIgniter
        $authorization = new AUTHORIZATION($CI); // Creas una instancia de la clase AUTHORIZATION

        $tokenData = array(
          'usuario'       => $returnData['response']['usuario'],
          'nombre'   => $returnData['response']['nombre'],
          'uuid'   => uniqid(),
          'timestamp' => $date,
          'iat' => time(),
          'expiration' => time() + ($CI->config->item('token_timeout') * 60),
          'in_session' => true
        );

        $message = array(
          'status' => 200, 'success' => true,
          'Authorization' =>  $authorization->generateToken($tokenData)
        );

        $this->response($message, $message['status']);
      }
    }
  } //termina login


  public function logout_post()
  {
    $headers = $this->input->request_headers();

    if (array_key_exists('Authorization', $headers) && !empty($headers['Authorization'])) {

      $token = $headers['Authorization'];
      $decodedToken = Authorization::validateToken($token);

      if ($decodedToken != false) {
        // Aquí puedes agregar la lógica para cerrar la sesión del usuario
        // $this->Authorize_model->cerrar_login_model($decodedToken);

        $this->response('adios vaquero!', 200);
      } else {
        $this->response('Error de autenticación', 401);
      }
    } else {

      $this->response('Token no proporcionado', 401);
    }
  }


  public function tokenRetrieve_post()
  {

    $message = array('status' => null, 'success' => false, 'messages' => '');

    #hacemos debug a los headers del navegador
    $headers = $this->input->request_headers();

    #$headers = $this->input->get_request_header('Authorization');


    if (array_key_exists('Authorization', $headers) && !empty($headers['Authorization'])) {
      $CI = &get_instance(); // Aquí obtienes la instancia de CodeIgniter
      $authorization = new AUTHORIZATION($CI); // Creas una instancia de la clase AUTHORIZATION
      $decodedToken = $authorization->validateTimestamp($headers['Authorization']);

      if ($decodedToken != false) {
        $this->set_response($decodedToken, $message['status']);
      } else {

        $message['messages'] = 'Token inválido';
        $this->set_response($message, REST_Controller::HTTP_UNAUTHORIZED);
      }
      
    } else {

      $message['messages'] = 'Token ausente';
      $this->set_response($message, REST_Controller::HTTP_UNAUTHORIZED);

    }

  }


}
