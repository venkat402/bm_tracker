<?php

defined('BASEPATH') OR exit('No direct script access allowed');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
echo '<pre>';

class BasicUpdateCron extends CI_Controller {

    public $server_conn;
    public $tracker_conn;

    public function __construct() {
        parent::__construct();
    }

    public function getServers() {
        $this->load->model('Home_model');
        $result = $this->db->query("SELECT domain_name,user_name,domain_password,db_name FROM domains_info");
        return $result->result_array();
    }

    public function connectServer($get_domain) {
        $get_domain = array_map('trim', $get_domain);
        extract($get_domain);
        $server_conn = new mysqli($domain_name, $user_name, $domain_password, $db_name);
        // Check connection
        if ($server_conn->connect_error) {
            $this->offlineServer($domain_name);
        }

        if ($server_conn->connect_error) {
            var_dump("$domain_name is failed to connected");
        } else {
            var_dump("$domain_name is connected");
        }
        return $server_conn;
    }

    public function offlineServer($domain_name) {
        $this->load->model('Home_model');
        $online_offline = "0";
        $result = $this->db->query("UPDATE domains_info SET  online_offline= '$online_offline'
	WHERE domain_name = '$domain_name'");
        var_dump("$domain_name is in offline");
    }

    public function getTimeZone() {
        date_default_timezone_set('Asia/Calcutta');
        $ist_time = date('d/m/Y h:i:s A');
        return $ist_time;
    }

    public function campaignProcess() {
        //campaign in processing state
        $result = $this->server_conn->query("SELECT * FROM campaign WHERE status='processing' OR status='sending'");
        if (!$result->num_rows > 0) {
            return null;
        }
        while ($row = $result->fetch_assoc()) {
            $campaign_id = $row['campaign_id'];
            $list_id = $row['list_id'];
            $date_of_added = $row['send_at'];
            $result1 = $this->server_conn->query("SELECT count(*) as count FROM list_subscriber WHERE list_id='$list_id' and status='confirmed'");
            $row1 = $result1->fetch_assoc();
            $list_count = $row1['count'];
            $result2 = $this->server_conn->query("SELECT count(*) as count FROM campaign_delivery_log WHERE campaign_id='$campaign_id'");
            $row2 = $result2->fetch_assoc();
            $campaign_count = $row2['count'];
            $percent = ($campaign_count / $list_count);
            $percent = ($percent * 100);
            $totalPercent[] = round($percent);
            $result3 = $this->server_conn->query("SELECT * FROM campaign_delivery_log where campaign_id = '$campaign_id' ORDER BY log_id DESC LIMIT 1");
            $row3 = $result3->fetch_assoc();
            $started_at = $date_of_added;
            $finished_at = $row3['date_added'];
            $seconds = strtotime("$finished_at") - strtotime("$started_at");
            $days = floor($seconds / 86400);
            $hours = floor(($seconds - ($days * 86400)) / 3600);
            $hourdiff = $hours . "_Hours";
            $hourdiffIs[] = $hourdiff;
        }
        $totalPercentResult = implode(",", $totalPercent);
        $hourdiffResult = implode(",", $hourdiffIs);
        $processResult = array(
            'totalPercentResult' => $totalPercentResult,
            'hourdiffResult' => $hourdiffResult,
        );
        unset($totalPercent, $hourdiffIs);
        return $processResult;
    }

    public function campaignPending() {
        //campaign pending sending
        $result = $this->server_conn->query("SELECT * FROM campaign WHERE status='pending-sending'");
        $campaign_pending_counter = 0;
        while ($row = $result->fetch_assoc()) {
            $started_at = $row['date_added'];
            $finished_at = $row['send_at'];
            $hourdiff = round((strtotime($finished_at) - strtotime($started_at)) / 3600, 1);
            $hourdiff = round($hourdiff);
            if (!$hourdiff >= 1) {
                $campaign_pending_counter++;
            }
        }
        return $campaign_pending_counter;
    }

    public function listVerification() {
        //list verification pending
        $result = $this->server_conn->query("SELECT count(*) as count FROM list WHERE status='verification-pending'");
        $row = $result->fetch_assoc();
        $list_verification_count = $row["count"];
        return $list_verification_count;
    }

    public function campaignOpenRate() {
        //campaign mails open rate count
        $result = $this->server_conn->query("SELECT campaign_id
                    FROM campaign
                    WHERE STATUS = 'sent'
                    ORDER BY campaign_id DESC
                    LIMIT 0 , 1");
        $row = $result->fetch_assoc();
        $campaign_id = $row['campaign_id'];
        $sql2 = $this->server_conn->query("select list_id from campaign where campaign_id='$campaign_id'");
        $row2 = $sql2->fetch_assoc();
        $that_list_id = $row2['list_id'];
        $sql3 = $this->server_conn->query("SELECT count(*) as count FROM list_subscriber where list_id='$that_list_id' and status='confirmed'");
        $row3 = $sql3->fetch_assoc();
        $that_list_count = $row3['count'];
        if ($that_list_count > 0) {
            $result2 = $this->server_conn->query("SELECT count(*) as count FROM campaign_track_open where campaign_id='$campaign_id'");
            while ($row = $result2->fetch_assoc()) {
                $email_track_open_count = $row["count"];
            }
        } else {
            $email_track_open_count = "12345";
        }
        $data = array(
            'list_count' => $that_list_count,
            'open_count' => $email_track_open_count,
        );
        return $data;
    }

    public function bounseCronStatus() {
        //bounce cron status
        $result = $this->server_conn->query("SELECT count(*) as count FROM bounce_server WHERE status='active'");
        $row = $result->fetch_assoc();
        $bounce_server = $row['count'];
        return $bounce_server;
    }

    public function deliveryServerStatus() {
        //delivery server status
        $result = $this->server_conn->query("SELECT count(*) as count FROM delivery_server WHERE status='active'");
        $row = $result->fetch_assoc();
        $delivery_server = $row['count'];
        return $delivery_server;
    }

    public function totalSubscribers() {
        //total list subscribers
        $result = $this->server_conn->query("SELECT count(*) as count  FROM list_subscriber where status = 'confirmed'");
        $row = $result->fetch_assoc();
        $total_list_subscribers_count = $row['count'];
        return $total_list_subscribers_count;
    }

    public function totalDeliveryEmailsCount() {
        //total emails count
        $result = $this->server_conn->query("SELECT count(*) as count FROM delivery_server_usage_log");
        $row = $result->fetch_assoc();
        $emails_total_count = $row['count'];
        return $emails_total_count;
    }

    public function totalDeliveryEmails30() {
        //email  last 30 days
        $result = $this->server_conn->query("SELECT count(*) as count FROM delivery_server_usage_log WHERE date_added BETWEEN (CURDATE( ) - INTERVAL 1 MONTH) AND CURDATE( )");
        $row = $result->fetch_assoc();
        $emails_30days_count = $row['count'];
        return $emails_30days_count;
    }

    public function mailsDeliveryStatus() {
        //check mails going or not
        $result = $this->server_conn->query("SELECT status FROM campaign_delivery_log ORDER BY log_id DESC LIMIT 100");
        $status_count = $result->num_rows;
        $campaign_delivery = '';
        while ($row = $result->fetch_assoc()) {
            $campaign_delivery[] = $row['status'];
        }
        if ($status_count == 0) {
            $campaign_delivery[] = 'success';
        }
        $campaign_delivery_success = in_array("success", $campaign_delivery);
        unset($campaign_delivery);
        return $campaign_delivery_success;
    }

    public function campaignTimeToDeliver() {
        //getting time difference between campaigns
        $campaign_time = '';
        $result = $this->server_conn->query("(SELECT * FROM `campaign` WHERE DATE(`date_added`) = CURDATE() - 1) UNION (SELECT * FROM `campaign` WHERE DATE(`date_added`) = CURDATE());");
        if ($result->num_rows > 0) {
            return null;
        }
        while ($row = $result->fetch_assoc()) {
            $started_at = $row['started_at'];
            $finished_at = $row['finished_at'];
            $hourdiff = round((strtotime($finished_at) - strtotime($started_at)) / 3600, 1);
            $hourdiff = round($hourdiff);
            $reply_to = $row['reply_to'];
            $campaign_time[] = $hourdiff;
        }
        @$campaign_time = implode(",", $campaign_time);
    }

    public function sendEmailsPerMinute() {
        //getting time emails per minumte
        $result = $this->server_conn->query("SELECT * FROM campaign where status='sent' ORDER BY campaign_id DESC LIMIT 10");
        $mails_per_minute_count = 100;
        while ($row = $result->fetch_assoc()) {
            $started_at = $row['started_at'];
            $finished_at = $row['finished_at'];
            $get_list_id = $row['list_id'];
            $to_time = strtotime("$started_at");
            $from_time = strtotime("$finished_at");
            $campaign_delivery_time = round(abs($to_time - $from_time) / 60);
            $get_list_count_now = $this->lastCampListCount($get_list_id);
            if ($get_list_count_now > 500) {
                if (!empty($get_list_count_now) && !empty($campaign_delivery_time)) {
                    return $mails_per_minute_count = round(($get_list_count_now / $campaign_delivery_time));
                }
            }
        }
        return $mails_per_minute_count;
    }

    public function lastCampListCount($get_list_id) {
        $result = $this->server_conn->query("select count(*) as count from list_subscriber where list_id='$get_list_id' and status ='confirmed'");
        $row2 = $result->fetch_assoc();
        $get_list_count_now = $row2['count'];
        return $get_list_count_now;
    }

    public function unconfirmedEmails() {
        //unconfirmed emails 
        $result = $this->server_conn->query("SELECT count(*) as count FROM list_subscriber WHERE status = 'unconfirmed'");
        $row = $result->fetch_assoc();
        $unconfirmed_emails = $row['count'];
        return $unconfirmed_emails;
    }

    public function setBounceServerActive() {
        $this->server_conn->query("UPDATE bounce_server SET status = 'active' where ");
    }

    public function setCampaignStatusProcessing() {
        // disabled
        echo '<pre>';
        $result = $this->server_conn->query("SELECT * FROM campaign WHERE status = 'processing'");
        $row = $result->fetch_assoc();
        $campaign_id = $row['campaign_id'];
        $list_id = $row['list_id'];
//        $campaign_id = '1750';
//        $list_id = '150';
        $result = $this->server_conn->query("SELECT count(*) as count FROM list_subscriber WHERE list_id = '$list_id' and status = 'confirmed'");
        $row = $result->fetch_assoc();
        $list_count = $row['count'];
        $result = $this->server_conn->query("SELECT count(*) as count FROM campaign_delivery_log WHERE campaign_id = '$campaign_id'");
        $row = $result->fetch_assoc();
        $delivery_count = $row['count'];
        var_dump('camp delivery  count = ' . $delivery_count . 'list count = ' . $list_count);
        $result = $this->server_conn->query("SELECT * FROM campaign_delivery_log WHERE campaign_id = '$campaign_id' ORDER BY log_id DESC LIMIT 1");
        $row = $result->fetch_assoc();
        var_dump($row);
        $result = $this->server_conn->query("select timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00'))");
        $row = $result->fetch_assoc();
        var_dump($row);
        die;
//        if ($delivery_count >= $list_count) {
//            $this->server_conn->query("UPDATE campaign SET status = 'sent' where campaign_id = '$campaign_id'");
//        }
//        die;
//        $this->server_conn->query("UPDATE campaign SET status = 'sending' where status = 'processing'");
//        die;
    }

    public function updateTime() {
        $this->load->model('Home_model');
        $date = $this->getTimeZone();
        if (!$this->db->query("UPDATE domains_info_note SET note_value = '$date' where note_key = 'BasicUpdateCron.php' and note_type='crons'")) {
            var_dump($this->db->_error_message());
        }
    }

    public function updateIssues() {
        $this->load->model('Home_model');
        $this->db->query("SELECT count(*) as count FROM bulkmail_bugs2.tickets WHERE status = '1'");
        $row = $result->fetch_assoc();
        $list_count = $row['count'];
        if (!$this->db->query("UPDATE domains_info_note SET note_value = '$date' where note_key = 'BasicUpdateCron.php' and note_type='crons'")) {
            var_dump($this->db->_error_message());
        }
    }

    public function updateToTracker($domain_name, $data) {
        $this->load->model('Home_model');
        //final update
        extract($data);
        $result = $this->db->query("UPDATE domains_info SET
        campaign_processing= '$campaignProcess[totalPercentResult]',
        campaign_pending_sending= '$campaignPending',
        list_verification_pending= '$listVerification',
        email_track_open_count= '$campaignOpenRate[open_count]',
        campaign_last_list_count='$campaignOpenRate[list_count]',
        bounce_cron= '$bounseCronStatus',
        delivery_cron= '$deliveryServerStatus',
        campaign_delivery= '$mailsDeliveryStatus',
        total_emails='$totalDeliveryEmailsCount',
        emails_30days='$totalDeliveryEmails30',
        campaign_time='$campaignProcess[hourdiffResult]',
        ist_time='$getTimeZone',
        toatal_unconfirmed_emails = '$unconfirmedEmails',
        totalSubscribers='$totalSubscribers',
        mails_per_minute='$sendEmailsPerMinute',
        online_offline='1'
	WHERE domain_name = '$domain_name'");
        if (!$result) {
            var_dump("$domain_name updateing domain failed=" . $this->db->_error_message() . "<br>");
        } else {
            var_dump("$domain_name Result : sucessfully updated");
        }
    }

    // basic update tracker process
    public function updateServers() {
        $get_domains = $this->getServers();
        foreach ($get_domains as $get_domain) {
            $this->server_conn = $this->connectServer($get_domain);
            if ($this->server_conn->connect_error) {
                unset($this->server_conn);
                continue;
            }
            $campaignProcess = $this->campaignProcess();
            $campaignPending = $this->campaignPending();
            $listVerification = $this->listVerification();
            $campaignOpenRate = $this->campaignOpenRate();
            $bounseCronStatus = $this->bounseCronStatus();
            $deliveryServerStatus = $this->deliveryServerStatus();
            $totalSubscribers = $this->totalSubscribers();
            $totalDeliveryEmailsCount = $this->totalDeliveryEmailsCount();
            $totalDeliveryEmails30 = $this->totalDeliveryEmails30();
            $mailsDeliveryStatus = $this->mailsDeliveryStatus();
            //$campaignTimeToDeliver = $this->campaignTimeToDeliver();
            $unconfirmedEmails = $this->unconfirmedEmails();
            $sendEmailsPerMinute = $this->sendEmailsPerMinute();
            $getTimeZone = $this->getTimeZone();
            $data = get_defined_vars();
            //$this->setBounceServerActive();
            //$this->setCampaignStatusProcessing();
            unset($data['get_domains']);
            $this->updateToTracker($get_domain['domain_name'], $data);
            unset($this->server_conn);
        }
        $this->updateTime();
    }

}
