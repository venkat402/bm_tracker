<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Home_model extends CI_Model {

    public $title;
    public $content;
    public $date;
    public $tracker_conn;
    public $server_conn;

    public function __construct() {
        parent::__construct();
    }

    public function get($id) {
        return $this->db->get_where('posts', array('id' => $id))->row();
    }

    public function get_all() {
        $query = $this->db->query('select * from domains_info');
        return $query->result_array();
    }

    public function insert() {
        $this->title = 'CodeIgniter 101';
        $this->content = '<p>Say what you want about CI, it still rocks</p>';
        $this->date = time();

        $this->db->insert('posts', $this);
    }

    public function update($id) {
        $this->title = 'CodeIgniter 101';
        $this->content = '<p>Say what you want about CI, it still rocks</p>';
        $this->date = time();

        $this->db->update('posts', $this, array('id' => $id));
    }

    public function delete($id) {
        $this->db->delete('posts', array('id' => $id));
    }

    function get_user_info($user_id) {
        $query = $this->db->query("SELECT * FROM users WHERE user_id = ?", array($user_id));
        return $query->result_array();
    }

    public function getServers() {
        $result = $this->db->query("SELECT domain_name,user_name,domain_password,db_name FROM domains_info");
        return $result->result_array();
    }

    // get credentials of pariticular server from tracker
    public function getCredentialsOfSingleServer($fromDb) {
        $result = $this->db->query("SELECT domain_name,user_name,domain_password,db_name FROM domains_info where domain_name = '$fromDb'");
        return $result->row_array();
    }

    public function getAllData() {
        $result = $this->db->query("select * from domains_info");
        return $result->result_array();
    }

    public function getTrackerNoteData() {
        $result = $this->db->query("select * from domains_info_note");
        return $result->result_array();
    }

    public function lastUpdate() {
        $result = $this->db->query("SELECT ist_time FROM domains_info ORDER BY id DESC LIMIT 1");
        $row = $result->result_array();
        $ist_time_db = $row['ist_time'];
        return $ist_time_db;
    }

    //connect to particular server
    public function connectServer($get_domain) {
        $get_domain = array_map('trim', $get_domain);
        extract($get_domain);
        $server_conn = new mysqli($domain_name, $user_name, $domain_password, $db_name);
        if (!$server_conn) {
            return 'failed to connect';
        }
        $this->server_conn = $server_conn;
        return $server_conn;
    }

    // getting time zone of india
    public function getTimeZone() {
        date_default_timezone_set('Asia/Calcutta');
        $ist_time = date('d/m/Y h:i:s A');
        return $ist_time;
    }

    //clients count chart reprecenation
    public function clientsCountChart() {
        $domain2 = $this->getServers();
        foreach ($domain2 as $domains) {
            $domains = $domains['domain_name'];
            $result = $this->db->query("SELECT user_id FROM user WHERE redirect='http://$domains' and status='active'");
            $user_ids = null;
            if ($result->num_rows() > 0) {
                foreach ($result->result_array() as $row) {
                    $user_ids[] = $row['user_id'];
                }
            }
            //$domains = substr("$domains", 0, -5);
            $ceo_count = 0;
            if ($result->num_rows() > 0) {
                $ceo_count = $result->num_rows();
            }
            $customer_count2 = null;
            if ($result->num_rows() > 0) {
                foreach ($user_ids as $user_id) {
                    $result = $this->db->query("select email from customer where created_by='$user_id' and status='active'");
                    $customer_count2[] = $result->num_rows();
                }
            }
            $total_count = 0;
            $customer_count_is = 0;
            if (!empty($customer_count2)) {
                $customer_count_is = array_sum($customer_count2);
            }
            if (!empty($customer_count2)) {
                $total_count = $ceo_count + array_sum($customer_count2);
            }
            $result2 = $this->db->query("SELECT * FROM domains_info WHERE domain_name='$domains'");
            $campaigns_30days = '';
            $emails_30days = '';
            $listsCount = '';
            if ($result2->num_rows() > 0) {
                $row2 = $result2->row_array();
                $campaigns_30days = $row2['campaigns_30days'];
                $emails_30days = $row2['emails_30days'];
                $listsCount = $row2['listsCount'];
                $totalSubscribers = $row2['totalSubscribers'];
            }
            $dataCharts[] = array(
                'customer_count' => "$customer_count_is", 'ceo_count' => "$ceo_count",
                "total_count" => $total_count, "campaigns_30days" => "$campaigns_30days",
                "emails_30days" => $emails_30days, "domain_name" => "$domains", "listsCount" => "$listsCount",
                "totalSubscribers" => $totalSubscribers,
            );
        }
        $domainsCount2 = count($dataCharts);

        $graph_bar10 = '';
        $graph_bar11 = '';
        foreach ($dataCharts as $dataChart) {
            $ceo_count2[] = $dataChart['ceo_count'];
            $customer_count2[] = $dataChart['customer_count'];
            $campaigns_30days2[] = $dataChart['campaigns_30days'];
            $emails_30days2[] = $dataChart['emails_30days'];
            $total_count2[] = $dataChart['total_count'];
            $listsCount2[] = $dataChart['listsCount'];
            $totalSubscribers2[] = $dataChart['totalSubscribers'];
            //emails usage
            if (empty($dataChart['emails_30days'])) {
                $dataChart['emails_30days'] = 0;
            }
            $domains = substr("$dataChart[domain_name]", 0, -5);
            //            $graph_bar10 .= "{domainName: $domains,emails: $dataChart[emails_30days]},";
            $graph_bar10[] = array(
                "domainName" => "$domains",
                "emails" => "$dataChart[emails_30days]",
            );
            //clients count
            if (empty($dataChart['total_count'])) {
                $dataChart['total_count'] = 0;
            }
            //            $graph_bar11 .= "{domainName: $domains,emails: $dataChart[total_count]},";
            $graph_bar11[] = array(
                "domainName" => "$domains",
                "emails" => "$dataChart[total_count]",
            );
        }


        $requiredData = array(
            'domainsCount2' => $domainsCount2,
            'ceo_count2' => array_sum($ceo_count2),
            'customer_count2' => array_sum($customer_count2),
            'campaigns_30days2' => array_sum($campaigns_30days2),
            'emails_30days2' => array_sum($emails_30days2),
            'total_count2' => array_sum($total_count2),
            'listsCount2' => array_sum($listsCount2),
            'totalSubscribers2' => array_sum($totalSubscribers2),
            'graph_bar10' => array_values($graph_bar10),
            'graph_bar11' => array_values($graph_bar11),
        );
        return $requiredData;
    }

    public function home2($domains) {
        $result = $this->db->query("select * from domains_info where domain_name='$domains'");
        if ($result->num_rows > 0) {
            $row = $result->result_array();
            $bounce_server = $row['bounce_server'];
            $delivery_server = $row['delivery_server'];
            $notexistcount = $row['notexistcount'];
            $unsubscribedcount = $row['unsubscribedcount'];
            $abuse = $row['abuse'];
            $blacklist_count = $row['domain_blacklist'];
            $campaign_ip_bounce_log = $row['campaign_ip_bounce_log'];
            $unconfirmed = $row['toatal_unconfirmed_emails'];
            $posthotlist_size = $row['posthotlist_size'];
            $posthotlist_confirmed = $row['posthotlist_confirmed'];
        }
        $domain_path = substr("$domains", 0, -5) . '_info';
        unset($result, $row);
        $data = get_defined_vars();
        return $data;
    }

    public function emailsUsage() {
        $result = $this->db->query("select domain_name,emails_30days from domains_info");
        foreach ($result->result_array() as $row) {
            if (empty($row['emails_30days'])) {
                $row['emails_30days'] = '0';
            }
            $emails_30days_count[] = $row['emails_30days'];
            $servernames[] = $row['domain_name'];
        }
        $maps_info = array_combine($servernames, $emails_30days_count);
        return $maps_info;
    }

    public function addDomain($data) {
        unset($data['addDomain']);
        $data = array_map('trim', $data);
        $new_domain = $data['domain'];
        $data = implode(", ", $data);
        $data = "'" . str_replace(",", "','", $data) . "'";
        $existDomains = $this->getServers();
        $domainNames = '';
        foreach ($existDomains as $existDomain) {
            $domainNames[] = $existDomain['domain_name'];
        }
        if (in_array("$new_domain", $domainNames)) {
            $response = 'domain already exist';
            return $response;
        } else {
            $result = $this->db->query("INSERT INTO domains_info " .
                    "(domain_name,user_name,domain_password,db_name)" .
                    "VALUES " .
                    "($data)");
            if (!$result) {
                $response = "failed to save the form";
            } else {
                $response = 'domain added sucessfully.';
            }
        }
        return $response;
    }

    public function updateDomain($data) {
        $update_domain = $data['update_domain'];
        $update_password = $data['update_password'];
        $update_dbname = $data['update_dbname'];
        $username = $data['update_username'];
        $existCheck = $this->db->query("select domain_name FROM domains_info WHERE domain_name = '$update_domain'");
        if (!$existCheck->num_rows() > 0) {
            return "Domain not exist in tracker";
        }
        $result = $this->db->query("UPDATE domains_info SET  domain_password= '$update_password',db_name='$update_dbname',user_name='$username' WHERE domain_name = '$update_domain'");
        if (!$result) {
            $response = "failed to save the form";
        } else {
            $response = 'domain updated sucessfully.';
        }
        return $response;
    }

    public function deleteDomain($domain_name) {
        $domain_name = trim($domain_name['delete']);
        $existCheck = $this->db->query("select domain_name FROM domains_info WHERE domain_name = '$domain_name'");
        if (!$existCheck->num_rows() > 0) {
            return "Domain not exist in tracker";
        }
        $result = $this->db->query("DELETE FROM domains_info WHERE domain_name = '$domain_name'");
        if (!$result) {
            $respose = 'Failed to delete domain';
        } else {
            $respose = 'Domain deleted successfully.';
        }
        return $respose;
    }

    public function searchClient($email) {
        $customer_email = null;
        $admin_emails = null;
        $redirect_urls = null;
        $admin_redirect = null;
        $admin_email_db = null;

        $user_email = trim($email);
        // customer table
        $result = $this->db->query("select email,created_by FROM customer WHERE email LIKE '%$user_email%'");
        $customer_count = $result->num_rows();
        if ($result->num_rows() > 0) {
            foreach ($result->result_array() as $row) {
                $customer_email[] = $row['email'];
                $created_bys[] = $row['created_by'];
            }
            foreach ($created_bys as $created_by) {
                $result = $this->db->query("select email,redirect FROM user WHERE user_id ='$created_by'");
                $row = $result->row_array();
                $admin_emails[] = $row['email'];
                $redirect_urls[] = $row['redirect'];
            }
        }
        //user table
        $result = $this->db->query("select email,redirect FROM user WHERE email LIKE '%$user_email%'");
        $admin_rows_count = $result->num_rows();
        if ($result->num_rows() > 0) {
            foreach ($result->result_array() as $row) {
                $admin_redirect[] = $row['redirect'];
                $admin_email_db[] = $row['email'];
            }
        }
        $data = array(
            'customer_email' => $customer_email,
            'admin_emails' => $admin_emails,
            'redirect_urls' => $redirect_urls,
            'admin_redirect' => $admin_redirect,
            'admin_email_db' => $admin_email_db
        );
        return $data;
    }

    public function moveCEO($data) {
        $data = array_map('trim', $data);
        $email = $data['email'];
        $fromDb = $data['fromDb'];
        $toDb = $data['toDb'];
        //checking in from server
        $server = $this->getCredentialsOfSingleServer($fromDb);
        if (empty($server)) {
            return $response = "$fromDb not exist in tracker";
        }
        $conn = $this->connectServer($server);
        if (!$conn) {
            return $response = "$fromDb failed to connect";
        }
        $result = $conn->query("select user_id from user where email='$email'");

        if (!$result->num_rows > 0) {
            return $response = "$email not exist in $fromDb";
        }
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $result = $conn->query("select email from customer where created_by = '$user_id'");
        $customerEmails = null;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $customerEmails[] = $row['email'];
            }
        }
        // checking in to server
        $server = $this->getCredentialsOfSingleServer($toDb);
        if (!empty($server)) {
            $conn = $this->connectServer($server);
        } else {
            return $response = "$toDb not exist in tracker";
        }
        if (!$conn) {
            return $response = "$toDb failed to connect";
        }
        $result = $conn->query("select email from user where email='$email'");
        if ($result->num_rows > 0) {
            return $response = "$email already exist in $toDb";
        }
        if (count($customerEmails) > 0) {
            foreach ($customerEmails as $customerEmail) {
                $result = $conn->query("select email from customer where email like '$customerEmail'");
                if ($result->num_rows > 0) {
                    return $response = "Not possible to move CEO clients already exist in $toDb : $customerEmail !";
                }
            }
        }
        // inserting into tracker
        $dateTime = $this->getTimeZone();
        $result = $this->db->query("INSERT INTO moveceo " .
                "(ceoEmail,fromDb,toDb,status,message,dateAdded)" .
                "VALUES " .
                "('$email','$fromDb','$toDb','pending-moving','added successfully','$dateTime')");
        if (!$result) {
            return $response = 'Failed to save data';
        }
        return $data = 'Form submitted successfully';
    }

    public function getmoveCEO() {
        $result = $this->db->query("select * from moveceo");
        return $result->result_array();
    }

    public function deletemoveCEO($id) {
        $result = $this->db->query("select * from moveceo where id = '$id'");
        if ($result->num_rows() > 0) {
            $result = $this->db->query("delete from moveceo where id = '$id'");
            if (!$result) {
                return $response = 'Failed to delete';
            }
            return $response = 'Record deleted successfully';
        }
        return $response = 'not exist';
    }

    public function changeUserRedirection($data) {
        unset($data['changeRedirect']);
        extract($data);
        $result = $this->db->query("select redirect from user where redirect = '$old_domain'");
        if (!$result->num_rows() > 0) {
            return "$old_domain redirect not exist";
        }
        $result = $this->db->query("UPDATE user SET redirect = '$new_domain' WHERE `user`.`redirect` = '$old_domain'");
        if (!$result) {
            return $data = 'Redirection failed';
        }
        return $data = 'Form submitted successfully';
    }

    public function trackerUnsubscribeEmail($email) {
        $email = $this->db->escape_str($email);
        $date = date("Y-m-d H:i:s");
        $result = $this->db->query("select * from email_blacklist where email='$email' limit 1");
        $row = $result->row_array();
        $dateOfAdded = $row['date_added'];
        if (!$result->num_rows() > 0) {
            $result = $this->db->query("INSERT INTO email_blacklist(`email_id`,`subscriber_id`,`email`,`reason`,`date_added`,`last_updated`)VALUES (NULL , NULL , '$email', 'Unsubscribed global', '$date', '$date')");
            if (!$result) {
                return $response = 'Unable to save the form';
            } else {
                return $response = 'Email unsubscribed successfully.';
            }
        } else {
            return $response = " Email already added on : $dateOfAdded ";
        }
    }

    public function addDomainExpireDate($data) {
        unset($data['domainExpire']);
        $domainName = $data['domainName'];
        $expireDate = $data['expireDate'];
        $date = $this->getTimeZone();
        $result = $this->db->query("select * from domains_info_note where note_type='domainExpire' and note_key='$domainName' limit 1");
        $row = $result->row_array();
        $dateOfAdded = $row['date_of_added'];
        if (!$result->num_rows() > 0) {
            $result = $this->db->query("INSERT INTO domains_info_note (note_type,note_key,note_value,date_of_added)"
                    . " VALUES ('domainExpire','$domainName','$expireDate','$date')");
            if (!$result) {
                return $response = 'Unable to save the form';
            } else {
                return $response = 'Domain expire date added successfully.';
            }
        } else {
            return $response = " Domain expire date already added on : $dateOfAdded ";
        }
    }

    public function addTrackerNote($data) {
        unset($data['addNote']);
        $typeName = $data['typeName'];
        $keyName = $data['keyName'];
        $valueName = $data['valueName'];
        $date = $this->getTimeZone();
        $result = $this->db->query("INSERT INTO domains_info_note (note_type,note_key,note_value,date_of_added)"
                . " VALUES ('$typeName','$keyName','$valueName','$date')");
        if (!$result) {
            return $response = 'Unable to save the form';
        } else {
            return $response = 'Form saved successfully.';
        }
    }

    public function getAllTrackerNotes() {
        $result = $this->db->query("select * from domains_info_note ORDER BY note_type");
        return $result->result_array();
    }

    public function getDomainExpireDates() {
        $result = $this->db->query("select * from domains_info_note where note_type = 'domainExpire'");
        foreach ($result->result_array() as $row) {
            date_default_timezone_set('Asia/Calcutta');
            $todayDate = date_create(date('Y-m-d'));
            $expireDate = date_create($row['note_value']);
            $diff = date_diff($todayDate, $expireDate);
            $daysLeft = $diff->format("%R%a");
            $row['daysLeft'] = $daysLeft;
            $data[] = $row;
        }
        return $data;
    }

    public function getDomainsInfoNoteData() {
        $result = $this->db->query("select * from domains_info_note");
        return $result->result_array();
    }

    public function deleteDomainExpireDate($id) {
        $id = trim($id);
        $result = $this->db->query("DELETE FROM domains_info_note WHERE id = '$id'");
        if (!$result) {
            $respose = 'Failed to delete ';
        } else {
            $respose = 'deleted successfully';
        }
        return $respose;
    }

    public function alertDomainExpireDates() {
        $data = $this->getDomainExpireDates();
        foreach ($data as $dataOne) {
                $daysLeft = $dataOne['daysLeft'];
                if (($daysLeft > -10 && $daysLeft < 30)) {
                    $expiringDomains[] = $dataOne;
                }
        }
        return $expiringDomains;
        return $data;
        $precentServers = $this->getServers();
        $expiringDomains = '';
        foreach ($precentServers as $precentServer) {
            $domainNames[] = $precentServer['domain_name'];
        }
        foreach ($data as $dataOne) {
            if (in_array($dataOne['note_key'], $domainNames)) {
                $daysLeft = $dataOne['daysLeft'];
                if (($daysLeft > -10 && $daysLeft < 30)) {
                    $expiringDomains[] = $dataOne;
                }
            }
        }
        return $expiringDomains;
    }

    public function showDomainData($domain) {
        $result = $this->db->query("select * from domains_info where domain_name='$domain'");
        return $result->row_array();
    }

    public function getCronLastUpdate() {
        $result = $this->db->query("SELECT ist_time FROM domains_info ORDER BY id DESC LIMIT 1");
        return $result->row_array();
    }

    public function dnsRecords($dnsDomainName) {
        $panelKey = "panel.$dnsDomainName";
        $mailDomainKey = "mail._domainkey.$dnsDomainName";
        $dmarcKey = "_dmarc.$dnsDomainName";
        $dnsRecords = dns_get_record("$dnsDomainName", DNS_ALL);
        if (empty($dnsRecords)) {
            return null;
        }
        $panelDnsRecords = dns_get_record($panelKey, DNS_ALL);
        $mailDomainRecords = dns_get_record($mailDomainKey, DNS_ALL);
        $dmarcRecords = dns_get_record($dmarcKey, DNS_ALL);
        foreach ($panelDnsRecords as $panelDnsRecord) {
            if ($panelDnsRecord['host'] == "$panelKey") {
                $PanelRecord[] = $panelDnsRecord;
            }
        }
        foreach ($mailDomainRecords as $mailDomainRecord) {
            if ($mailDomainRecord['host'] == "$mailDomainKey") {
                $mailRecord[] = $mailDomainRecord;
            }
        }
        foreach ($dmarcRecords as $dmarcRecord) {
            if ($dmarcRecord['host'] == "$dmarcKey") {
                $dmarcingRecord[] = $dmarcRecord;
            }
        }

        return array_merge($dnsRecords, $PanelRecord, $mailRecord, $dmarcingRecord);
    }

    public function getIssues() {
        $result = $this->db->query("SELECT * FROM mantis_bug_table WHERE status < 80");
        return $result->result_array();
    }

    public function siteUrl() {
        $trackerNoteDatas = $this->getTrackerNoteData();
        foreach ($trackerNoteDatas as $trackerNoteData) {
            if ($trackerNoteData['note_type'] == 'siteUrl') {
                return $trackerNoteData['note_key'];
            }
        }
    }

}
