<?php
defined('BASEPATH') or exit('No direct script access allowed');
use Restserver\Libraries\REST_Controller;

require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';
class Home extends REST_Controller
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

    public function home_get()
    {
        $allServersRecords = $this->Home_model->getAllData();
        $home_data['dataCharts'] = $this->Home_model->clientsCountChart();
        $home_data['trackerNoteDatas'] = $this->Home_model->getTrackerNoteData();
        $home_data['expireyDomains'] = $this->Home_model->alertDomainExpireDates();
        $home_data['trackerNoteDatas'] = $this->Home_model->getTrackerNoteData();
        $home_data['ist_time'] = $this->Home_model->getCronLastUpdate();
        foreach ($allServersRecords as $allServersRecord) {
            //campaign process tool tip
            $campaign_processing_str = '';
            if (!empty($allServersRecord['campaign_processing'])) {
                $campaign_processingCount = count(explode(",", $allServersRecord['campaign_processing']));
                $campaign_processingPercentages = explode(",", $allServersRecord['campaign_processing']);
                $campaign_processingTimes = explode(",", $allServersRecord['campaign_time']);
                $campaign_processing_str = '';
                for ($i = 0; $i < $campaign_processingCount; $i++) {
                    $campaign_processing_str .= 'process_' . $campaign_processingPercentages[$i] . "%_From_" . $campaign_processingTimes[$i] . " ";
                }
            }
            $allServersRecord['campaign_processing_str'] = $campaign_processing_str;
            $allServersRecord['campaign_processingCount'] = $campaign_processingCount;
            $allServersRecord['delivery_server'] = implode(", ", explode(",", $allServersRecord['delivery_server']));
            $allServersRecord['bounce_server'] = implode(", ", explode(",", $allServersRecord['bounce_server']));
            if (empty($allServersRecord['domain_blacklist']) && empty($allServersRecord['emailReject'])) {
                $allServersRecord['domain_blacklistCount'] = 0;
                $allServersRecord['domain_blacklist'] = 'Not Listed';
            } else {
                if (empty($allServersRecord['emailReject'])) {
                    $loffarBlacklist = $allServersRecord['domain_blacklist'];
                } else {
                    $domain_blacklist = $allServersRecord['domain_blacklist'];
                    $emailReject = $allServersRecord['emailReject'];
                    $loffarBlacklist = "$domain_blacklist,$emailReject";
                }
                $allServersRecord['domain_blacklistCount'] = count(explode(",", $loffarBlacklist));
                $domain_blacklist = implode(", ", explode(",", $loffarBlacklist));
                $allServersRecord['domain_blacklist'] = $domain_blacklist;
            }
            @$allServersRecord['require_list_blacklist_percentage'] = round(($allServersRecord['blacklist_total'] / $allServersRecord['totalSubscribers']) * 100);
            if ($allServersRecord['require_list_blacklist_percentage'] > 100) {
                $allServersRecord['require_list_blacklist_percentage'] = 100;
            }
            @$allServersRecord['blacklistToggle'] = "Blacklist_Emails_:" . ($allServersRecord['blacklist_total'] - $allServersRecord['campaign_ip_bounce_log']) . " Ip_Bounce_:" . $allServersRecord['campaign_ip_bounce_log'];



            $requireAllServersRecords[] = $allServersRecord;
            unset($allServersRecord, $campaign_processingCount, $campaign_processingPercentages, $campaign_processingTimes, $loffarBlacklist, $domain_blacklist, $emailReject);
        }
        $home_data['allServersRecords'] = $requireAllServersRecords;
        //links in view 
        foreach ($home_data['trackerNoteDatas'] as $trackerNoteData) {
            if ($trackerNoteData['note_type'] == 'mxtoolbox' && $trackerNoteData['note_key'] == 'blacklist') {
                $requiredData['blacklistLink'] = $trackerNoteData;
            }
            if ($trackerNoteData['note_type'] == 'EValidationDomain') {
                $requiredData['emailValidationLink'] = $trackerNoteData;
            }
            if ($trackerNoteData['note_type'] == 'spamScore') {
                $requiredData['spamScore'] = $trackerNoteData;
            }
            if ($trackerNoteData['note_type'] == 'ciscoTalos') {
                $requiredData['ciscoTalos'] = $trackerNoteData;
            }
            if ($trackerNoteData['note_type'] == 'mailTester') {
                $requiredData['mailTester'] = $trackerNoteData;
            }
            if ($trackerNoteData['note_type'] == 'clientsFailRedirect') {
                $requiredData['clientsFailRedirect'] = json_decode($trackerNoteData['note_value']);
            }
            if ($trackerNoteData['note_type'] == 'duplicateClients') {
                $requiredData['duplicateClients'] = json_decode($trackerNoteData['note_value']);
            }
        }
        $home_data['requiredData'] = $requiredData;
        $this->response($home_data);
    }

    public function sidenav_get()
    {
        $sidenav_data['getIssues'] = $this->Home_model->getIssues();
        $this->response($sidenav_data);
    }

    public function backend_get()
    {
        $datas = $this->Home_model->getAllData();
        foreach ($datas as $data) {
            $removes = array('id', 'user_name', 'domain_password', 'db_name', 'bandwidth');
            foreach ($removes as $remove) {
                unset($data["$remove"]);
            }
            $require_data[] = $data;
        }
        $home_data['datas'] = $require_data;
        $home_data['datas_keys'] = array_keys($data);
        $this->response($home_data);
    }

    public function usage_chart_get()
    {
        $graph_bar7 = '';
        $graph_bar2 = '';
        $graph_bar3 = '';
        $graph_bar4 = '';
        $graph_bar5 = '';
        $graph_bar6 = '';
        $graph_bar8 = '';
        foreach ($this->Home_model->getAllData() as $data) {
            if (empty($data['emails_30days'])) {
                $data['emails_30days'] = 0;
            }
            $graph_bar7[] = array(
                "domainName" => "$data[domain_name]",
                "emails" => "$data[emails_30days]"
            );
            //blacklist monitor
            $blacklistMonitor = $data['campaign_ip_bounce_log'] + $data['blacklist_30days'];
            if (empty($blacklistMonitor)) {
                $blacklistMonitor = 0;
            }
            $graph_bar2[] = array(
                "domainName" => "$data[domain_name]",
                "emails" => "$blacklistMonitor"
            );
            //mails per minute
            if (empty($data['mails_per_minute'])) {
                $data['mails_per_minute'] = 0;
            }
            $graph_bar3[] = array(
                "domainName" => "$data[domain_name]",
                "emails" => "$data[mails_per_minute]"
            );
            //domain blacklist count 
            $domain_blacklist = substr_count($data['domain_blacklist'], ",");
            if (empty($domain_blacklist)) {
                $domain_blacklist = 0;
            }
            $graph_bar4[] = array(
                "domainName" => "$data[domain_name]",
                "emails" => "$domain_blacklist"
            );
            //campaigns 30 days 
            if (empty($data['campaigns_30days'])) {
                $data['campaigns_30days'] = 0;
            }
            $graph_bar5[] = array(
                "domainName" => "$data[domain_name]",
                "emails" => "$data[campaigns_30days]"
            );

            $total_count = (count(explode(",", $data['ceo_emails'])) + count(explode(",", $data['client_emails'])));
            if (empty($total_count)) {
                $total_count = 0;
            }
            $graph_bar6[] = array(
                "domainName" => "$data[domain_name]",
                "emails" => "$total_count",
            );

            $graph_bar8_data[] = json_decode($data['advancedData'], true);
        }
        foreach ($graph_bar8_data as $graph_bar8_datains_keys) {
            if (empty($graph_bar8_datains_keys)) {
                continue;
            }
            foreach ($graph_bar8_datains_keys as $graph_bar8_datains_key) {
                if (empty($graph_bar8_datains_key)) {
                    continue;
                }
                $graph_bar8[] = array(
                    "customer_email" => "$graph_bar8_datains_key[customer_email]",
                    "emailUsage" => "$graph_bar8_datains_key[emailUsage]",
                );
            }
        }
        $home_data['graph_bar7'] = $graph_bar7;
        $home_data['blacklistMonitor'] = $blacklistMonitor;
        $home_data['graph_bar2'] = $graph_bar2;
        $home_data['graph_bar3'] = $graph_bar3;
        $home_data['domain_blacklist'] = $domain_blacklist;
        $home_data['graph_bar4'] = $graph_bar4;
        $home_data['graph_bar5'] = $graph_bar5;
        $home_data['graph_bar6'] = $graph_bar6;
        $home_data['graph_bar8'] = $graph_bar8;
        $this->response($home_data);
    }

    public function search_post()
    {
        $_POST = json_decode(file_get_contents('php://input'), true);

        $this->form_validation->set_rules('email', 'Email', 'required|trim|xss_clean');
        if ($this->form_validation->run() == false) {
            $settings_data['resultOfSearchs'] = strip_tags(validation_errors());
            $this->set_response($settings_data, REST_Controller::HTTP_CREATED);
            return;
        }
        if ((!empty($_POST['email']))) {
            $home_data['resultOfSearchs'] = $this->Home_model->searchClient($_POST['email']);
        }
        $home_data['client_email'] = $_POST['email'];
        if (empty($home_data['resultOfSearchs'])) {
            $home_data['resultOfSearchs'] = 'Failed to get data.';
        }
        $this->response($home_data);
    }

    public function show_domain_get($domains)
    {
        $row = $this->Home_model->showDomainData($domains);
        $removes = array(
            'id', 'domain_name', 'user_name', 'domain_password', 'db_name',
            'final_blacklisted_emails',
            'require_list_name', 'require_list_count', 'require_list_blacklist_count', 'require_list_date_added'
        );
        foreach ($removes as $remove) {
            unset($row["$remove"]);
        }

        $home_data['row'] = $row;
        $home_data['emailsUsage'] = $row['advancedData'];
        $this->response($home_data);
    }

    public function ci_get()
    {
        $this->load->model('Home_model');
        $home_data['serverCredentials'] = $this->Home_model->getServers();
        $this->response($home_data);
    }

    public function dns_get($dnsDomainName)
    {
        if (empty($dnsDomainName)) {
            $this->set_response([
                'status' => false,
                'message' => 'domain could not be found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
        $home_data['dnsRecords'] = $this->Home_model->dnsRecords($dnsDomainName);
        $home_data['dnsDomainName'] = $dnsDomainName;
        $text_info = '';
        foreach ($home_data['dnsRecords'] as $dnsRecord) {
            $text_info .= $dnsRecord['type'] . ', ';
        }
        $home_data['text_info'] = $text_info;
        $this->response($home_data);
    }
}
