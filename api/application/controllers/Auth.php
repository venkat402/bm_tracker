<?php
defined('BASEPATH') or exit('No direct script access allowed');
use Restserver\Libraries\REST_Controller;

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Auth extends REST_Controller
{
    /**
     * __construct function.
     * 
     * @access public
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('security');
        $this->load->library('session');
        $this->load->helper(array('url'));
        $this->load->model('Auth_model');
    }


    /**
     * login function.
     * 
     * @access public
     * @return void
     */
    public function login_post()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);
		
		// load form helper and validation library
        $this->load->helper('form');
        $this->load->library('form_validation');
		
		// set validation rules
        $this->form_validation->set_rules('email', 'Username', 'trim|required|xss_clean');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean');

        if ($this->form_validation->run() == false) {
            $this->response(strip_tags(validation_errors()));
        } else {			
			// set variables from the form
            $username = $this->input->post('email');
            $password = $this->input->post('password');

            $username = trim($username);
            $password = trim($password);
            if (($username == "superadmin@technohrm.com") && ($password == "Techno@123")) {
                $user = array(
                    "email" => "superadmin@technohrm.com",
                );
                $this->session->userdata = $user;
                $this->response('Login success');
            } else {
                	// login failed
                $this->response('Wrong email or password');
            }
        }

    }

    /**
     * logout function.
     * 
     * @access public
     * @return void
     */
    public function logout_get()
    {
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }			
        $this->response('Loged out successfully');
    }

}
