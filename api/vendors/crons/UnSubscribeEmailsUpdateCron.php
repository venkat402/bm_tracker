<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//unsubscribe emails update cron
class UnSubscribeEmailsUpdateCron {

    public $server_conn;
    public $tracker_conn;

    public function getServers() {
        require_once ( dirname(__DIR__) . '/connect.php');
        $db = new Connect();
        $this->tracker_conn = $db->conn();
        $result = $this->tracker_conn->query("SELECT domain_name,user_name,domain_password,db_name FROM domains_info");
        $get_domains = '';
        while ($row = $result->fetch_assoc()) {
            $get_domains[] = $row;
        }
        return $get_domains;
    }

    public function trackerDbConnect() {
        if (!empty($this->tracker_conn)) {
            return $this->tracker_conn;
        }
        require_once ( dirname(__DIR__) . '/connect.php');
        $db = new Connect();
        $this->tracker_conn = $db->conn();
    }

    public function connectServer($get_domain) {
        $get_domain = array_map('trim', $get_domain);
        extract($get_domain);
        $server_conn = new mysqli($domain_name, $user_name, $domain_password, $db_name);
        if ($server_conn->connect_error) {
            var_dump("$domain_name is failed to connected");
        } else {
            var_dump("$domain_name is connected");
        }
        return $server_conn;
    }

    public function getTimeZone() {
        date_default_timezone_set('Asia/Calcutta');
        $ist_time = date('d/m/Y h:i:s A');
        return $ist_time;
    }

    public function trackerExistedEmails() {
        $result = $this->tracker_conn->query("SELECT email FROM email_blacklist");
        while ($row = $result->fetch_assoc()) {
            $tracker_existed_emails[] = $row['email'];
        }
        return array_unique(array_map('trim', $tracker_existed_emails));
    }

    public function unsubscribedEmailsFromDomain() {
        $result = $this->server_conn->query("SELECT email FROM `email_blacklist` WHERE `reason` LIKE '%Unsubscribed global%'");
        while ($row = $result->fetch_assoc()) {
            $unsubscribed_emails_from_domain[] = $row['email'];
        }
        return array_unique(array_map('trim', $unsubscribed_emails_from_domain));
    }

    public function totalBlacklistExistedEmails() {
        $result = $this->server_conn->query("SELECT email FROM email_blacklist");
        while ($row = $result->fetch_assoc()) {
            $total_blacklisted_existed_emails[] = $row['email'];
        }
        return array_unique(array_map('trim', $total_blacklisted_existed_emails));
    }

    public function getData() {
        $unsubscribed_emails_from_domain = array_map('strtolower', $this->unsubscribedEmailsFromDomain());
        $tracker_existed_emails = array_map('strtolower', $this->trackerExistedEmails());
        $totalBlacklistExistedEmails = array_map('strtolower', $this->totalBlacklistExistedEmails());
        $unsubscribers_add_to_tracker = array_diff($unsubscribed_emails_from_domain, $tracker_existed_emails);
        $unsubscribers_add_to_domain = array_diff($tracker_existed_emails, $unsubscribed_emails_from_domain, $totalBlacklistExistedEmails);
        return $data = array(
            'unsubscribers_add_to_tracker' => array_map(array($this->server_conn, 'real_escape_string'), $unsubscribers_add_to_tracker),
            'unsubscribers_add_to_domain' => array_map(array($this->server_conn, 'real_escape_string'), $unsubscribers_add_to_domain)
        );
    }

    public function insertUnSubscribersToDomain($data) {
        $unsubscribers_add_to_domain = $data['unsubscribers_add_to_domain'];
        $date = date("Y-m-d H:i:s");
        foreach ($unsubscribers_add_to_domain as $insert_subscriber) {
            $sqlValues[] = " (NULL , NULL , '$insert_subscriber', 'Unsubscribed global', '$date', '$date') ";
            $sqlValuesForEmailOnlly[] = "'$insert_subscriber'";
        }
        $count = count($sqlValuesForEmailOnlly);
        $result = $this->server_conn->query('INSERT INTO `email_blacklist`(`email_id`,`subscriber_id`,`email`,`reason`,`date_added`,`last_updated`) VALUES ' . implode(',', $sqlValues));
        if (!empty($result)) {
            var_dump("$count emails inserted to domain successfully");
        }
        if ($this->server_conn->error) {
            var_dump($this->server_conn->error);
        }
        $result = $this->server_conn->query("UPDATE list_subscriber SET status='unsubscribed' WHERE email in(" . implode(',', $sqlValuesForEmailOnlly) . ")");

        if (!empty($result)) {
            var_dump("$count emails status updated successfully");
        }
        if ($this->server_conn->error) {
            var_dump($this->server_conn->error);
        }
    }

    public function insertUnSubscribersToTracker($data) {
        $unsubscribers_add_to_tracker = $data['unsubscribers_add_to_tracker'];
        $date = date("Y-m-d H:i:s");
        foreach ($unsubscribers_add_to_tracker as $new_unsubscriber) {
            $sqlValues[] = " (NULL,NULL,'$new_unsubscriber','Unsubscribed global','$date', '') ";
        }
        $count = count($sqlValues);
        $result = $this->tracker_conn->query("INSERT INTO `email_blacklist`(`email_id`,`subscriber_id`,`email`,`reason`,`date_added`,`last_updated`)
                        VALUES" . implode(',', $sqlValues));
        if (!empty($result)) {
            var_dump("$count emails inserted to tracker successfully");
        }
        if ($this->tracker_conn->error) {
            var_dump($this->tracker_conn->error);
        }
    }

    public function updateTime() {
        $date = $this->getTimeZone();
        if (!$this->tracker_conn->query("UPDATE domains_info_note SET note_value = '$date' where note_key = 'UnSubscribeEmailsUpdateCron.php' and note_type='crons'")) {
            var_dump($this->tracker_conn->error);
        }
    }

    public function updateServers() {
        $get_domains = $this->getServers();
        foreach ($get_domains as $get_domain) {
            $this->server_conn = $this->connectServer($get_domain);
            if ($this->server_conn->connect_error) {
                unset($this->server_conn);
                continue;
            }
            $data = $this->getData();
            if (!empty($data['unsubscribers_add_to_domain'])) {
                $this->insertUnSubscribersToDomain($data);
            }
            if (!empty($data['unsubscribers_add_to_tracker'])) {
                $this->insertUnSubscribersToTracker($data);
            }

            if (empty($data['unsubscribers_add_to_domain']) && empty($data['unsubscribers_add_to_tracker'])) {
                var_dump("No data found");
            }
            unset($data);
            unset($this->server_conn);
        }
        $this->updateTime();
    }

}

echo '<pre>';
$UnSubscribeEmailsUpdateCron = new UnSubscribeEmailsUpdateCron();
$UnSubscribeEmailsUpdateCron->updateServers();
