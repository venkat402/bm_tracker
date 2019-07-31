<?php
defined('BASEPATH') or exit('No direct script access allowed');
use Restserver\Libraries\REST_Controller;

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class Settings extends REST_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Home_model');
        $this->load->library('form_validation');
        $this->load->helper('security');
        $this->load->library('session');
        if ($this->session->userdata('email') == '') {
            $this->response("Session required");
        }
    }

    public function manage_servers_post()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);
        if ((!empty($_POST['domain']))) {
            $this->form_validation->set_rules('domain', 'Domain Name', 'trim|required|xss_clean|max_length[150]');
            $this->form_validation->set_rules('username', 'User Name', 'trim|required|xss_clean|max_length[100]');
            $this->form_validation->set_rules('password', 'Password', 'trim|required|xss_clean|max_length[100]');
            $this->form_validation->set_rules('dbname', 'Database Name', 'trim|required|xss_clean|max_length[150]');
            if ($this->form_validation->run() == false) {
                $settings_data['data_result1'] =  strip_tags(validation_errors());
                $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
                return;
            }
            $settings_data['data_result1'] = $this->Home_model->addDomain($_POST);
        }
        if (!empty($_POST['update_domain'])) {
            $this->form_validation->set_rules('update_domain', 'Domain Name', 'trim|required|xss_clean|max_length[150]');
            $this->form_validation->set_rules('update_username', 'User Name', 'trim|required|xss_clean|max_length[100]');
            $this->form_validation->set_rules('update_password', 'Password', 'trim|required|xss_clean|max_length[100]');
            $this->form_validation->set_rules('update_dbname', 'Database Name', 'trim|required|xss_clean|max_length[150]');
            if ($this->form_validation->run() == false) {
                $settings_data['data_result2'] = strip_tags(validation_errors());
                $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
                return;
            }
            $settings_data['data_result2'] = $this->Home_model->updateDomain($_POST);
        }
        if (!empty($_POST['delete'])) {
            $this->form_validation->set_rules('delete', 'Domain Name', 'trim|required|xss_clean');
            if ($this->form_validation->run() == false) {
                $settings_data['data_result3'] = strip_tags(validation_errors());
                $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
                return;
            }
            $settings_data['data_result3'] = $this->Home_model->deleteDomain($_POST);
        }
        if (!empty($_POST['old_domain'])) {
            $this->form_validation->set_rules('new_domain', 'New Domain Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('old_domain', 'Old Domain Name', 'trim|required|xss_clean');
            if ($this->form_validation->run() == false) {
                $settings_data['data_result4'] = strip_tags(validation_errors());
                $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
                return;
            }
            $settings_data['data_result4'] = $this->Home_model->changeUserRedirection($_POST);
        }
        if (empty($settings_data)) {
            $settings_data['data_result'] = 'Failed to get data.';
        }
        $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
    }

    public function tracker_note_get()
    {
        $settings_data['trackerNoteData'] = $this->Home_model->getAllTrackerNotes();
        $settings_data['ExpireDates'] = $this->Home_model->getDomainExpireDates();
        $settings_data['getIssues'] = $this->Home_model->getIssues();
        $this->response($settings_data);
    }

    public function tracker_note_expire_post()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);
        if (!empty($_POST['update_domain'])) {
            $this->form_validation->set_rules('domainName', 'Domain Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('expireDate', 'Expire Date', 'trim|required|xss_clean');
            if ($this->form_validation->run() == false) {
                $settings_data['expire_data_result'] = strip_tags(validation_errors());
                $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
                return;
            }
            $settings_data['data_result2'] = $this->Home_model->updateDomain($_POST);
        }
        if ((!empty($_POST['domainName']))) {
            $settings_data['expire_data_result'] = $this->Home_model->addDomainExpireDate($_POST);
        }
        $settings_data['trackerNoteData'] = $this->Home_model->getAllTrackerNotes();
        $settings_data['ExpireDates'] = $this->Home_model->getDomainExpireDates();
        if (empty($settings_data['expire_data_result'])) {
            $settings_data['expire_data_result'] = 'Failed to get data.';
        }
        $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
    }

    public function tracker_note_data_post()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);
        if (!empty($_POST['typeName'])) {
            $this->form_validation->set_rules('typeName', 'Domain Name', 'trim|required|xss_clean');
            $this->form_validation->set_rules('keyName', 'Expire Date', 'trim|required|xss_clean');
            $this->form_validation->set_rules('valueName', 'Expire Date', 'trim|required|xss_clean');
            if ($this->form_validation->run() == false) {
                $settings_data['note_data_result'] = strip_tags(validation_errors());
                $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
                return;
            }
            $settings_data['note_data_result'] = $this->Home_model->addTrackerNote($_POST);
        }
        $settings_data['trackerNoteData'] = $this->Home_model->getAllTrackerNotes();
        $settings_data['ExpireDates'] = $this->Home_model->getDomainExpireDates();

        if (empty($settings_data['note_data_result'])) {
            $settings_data['note_data_result'] = 'Failed to get data.';
        }
        $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
    }

    public function delete_tracker_note_post()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $settings_data['data_result'] = $data;
        $id = $data['id'];
        if (!empty($id)) {
            $settings_data['data_result'] = $this->Home_model->deleteDomainExpireDate($id);
        }
        if (empty($settings_data['data_result'])) {
            $settings_data['data_result'] = 'Failed to get data to delete';
        }
        $settings_data['trackerNoteData'] = $this->Home_model->getAllTrackerNotes();
        $settings_data['ExpireDates'] = $this->Home_model->getDomainExpireDates();

        $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
    }

    public function domain_health_get()
    {
        $this->load->model('Home_model');
        $settings_data['getIssues'] = $this->Home_model->getIssues();
        $settings_data['allServersRecords'] = $this->Home_model->getAllData();
        $this->response($settings_data);
    }

    public function move_ceo_get()
    {
        $settings_data['getIssues'] = $this->Home_model->getIssues();
        $settings_data['data'] = $this->Home_model->getmoveCEO();
        $this->response($settings_data);
    }

    public function move_ceo_post()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);

        if (!empty($_POST['email'])) {
            $this->form_validation->set_rules('email', 'Email', 'trim|required|xss_clean');
            $this->form_validation->set_rules('fromDb', 'From Database', 'trim|required|xss_clean');
            $this->form_validation->set_rules('toDb', 'To database', 'trim|required|xss_clean');
            if ($this->form_validation->run() == false) {
                $settings_data['data_result'] = strip_tags(validation_errors());
                $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
                return;
            }
            $settings_data['data_result'] = $this->Home_model->moveCEO($_POST);
        } else {
            $settings_data['data_result'] = 'Failed to get ceo email.';
        }
        $settings_data['data'] = $this->Home_model->getmoveCEO();
        $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
    }

    public function delete_move_ceo_post()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'];
        if (!empty($id)) {
            $data_result = $this->Home_model->deletemoveCEO($id);
        } else {
            $settings_data['data_result'] = 'Failed to get data.';
        }
        if (!empty($data_result)) {
            $settings_data['data_result'] = $data_result;
        }
        $settings_data['data'] = $this->Home_model->getmoveCEO();
        $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
    }

    public function tracker_unsubscribe_post()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $settings_data['data_result'] = strip_tags(validation_errors());
            $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
            return;
        }
        $email = $_POST['email'];
        if (!empty($email)) {
            $data_result = $this->Home_model->trackerUnsubscribeEmail($email);
        } else {
            $settings_data['data_result'] = 'unable to get email';
        }
        if (!empty($data_result)) {
            $settings_data['data_result'] = $data_result;
        }
        $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
    }
}
