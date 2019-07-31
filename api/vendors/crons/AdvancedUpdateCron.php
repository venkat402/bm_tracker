<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class AdvancedUpdateCron {

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

    public function offlineServer($domain_name) {
        $online_offline = "offline";
        $result = $this->tracker_conn->query("UPDATE domains_info SET  online_offline= '$online_offline'
	WHERE domain_name = '$domain_name'");
        var_dump("$domain_name is in offline");
    }

    public function getTimeZone() {
        date_default_timezone_set('Asia/Calcutta');
        $ist_time = date('d/m/Y h:i:s A');
        return $ist_time;
    }

    //get_bounce server details
    public function bounseServers() {
        $result = $this->server_conn->query("select * from bounce_server where status='active'");
        while ($row = $result->fetch_assoc()) {
            $bounce_servers[] = $row['username'];
        }
        return implode(",", $bounce_servers);
    }

    //get_delivery server details
    public function deliveryServer() {
        $result = $this->server_conn->query("select * from delivery_server where status='active'");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $delivery_servers[] = $row['username'];
            }
        }
        return implode(",", $delivery_servers);
    }

    //notexist emails count
    public function notExistEmails() {
        $result = $this->server_conn->query("SELECT count(*) as count FROM list_subscriber WHERE STATUS='notexist'");
        $row = $result->fetch_assoc();
        $notexistcount = $row['count'];
        return $notexistcount;
    }

    //unsubscribed emails count
    public function unsubscribeEmails() {
        $result = $this->server_conn->query("SELECT count(*) as count FROM list_subscriber WHERE STATUS='unsubscribed'");
        $row = $result->fetch_assoc();
        $unsubscribedcount = $row['count'];
        return $unsubscribedcount;
    }

    //campaign abuse report
    public function campaignAbuse() {
        $result = $this->server_conn->query("SELECT count(*) as count FROM campaign_abuse_report");
        $row = $result->fetch_assoc();
        $abuse = $row['count'];
        return $abuse;
    }

    // select user deatils
    public function userDetails() {
        $result = $this->server_conn->query("select user_id from user");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ceo_user_id[] = $row['user_id'];
            }
        }
        return $ceo_user_id;
    }

    //getting customers details 
    public function customerDetails($ceo_user_id) {
        foreach ($ceo_user_id as $ceo_user_ids) {
            $result = $this->server_conn->query("select customer_id,email,created_by from customer where created_by='$ceo_user_ids'");
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $customerDetails[] = $row;
                }
            }
        }
        return $customerDetails;
    }

    public function allCampaigns() {
        //all campaigns 
        $result = $this->server_conn->query("SELECT count(*) as count FROM campaign WHERE status='sent'");
        $row = $result->fetch_assoc();
        $campaign_total_count = $row['count'];
        return $campaign_total_count;
    }

    public function campaignsLast30Days() {
        //campaign from last 30 days
        $result = $this->server_conn->query("SELECT count(*) as count FROM campaign WHERE send_at BETWEEN (CURDATE( ) - INTERVAL 1 MONTH) AND CURDATE( ) AND status='sent'");
        $row = $result->fetch_assoc();
        $campaign_30days_count = $row['count'];
        return $campaign_30days_count;
    }

    public function campaignsLast7Days() {
        //campaign_from last 7 days
        $result = $this->server_conn->query("SELECT count(*) as count FROM campaign WHERE send_at BETWEEN (CURDATE( ) - INTERVAL 7 DAY) AND CURDATE( ) AND status='sent'");
        $row = $result->fetch_assoc();
        $campaign_7days_count = $row['count'];
        return $campaign_7days_count;
    }

    public function campaignsToday() {
        //campaigns today and yester day
        $result = $this->server_conn->query("SELECT count(*) as count FROM campaign WHERE date_added >= CURDATE()");
        $row = $result->fetch_assoc();
        $campaign_today_count = $row['count'];
        return $campaign_today_count;
    }

    public function totalUnsubscribers() {
        //total unsubscribed emails
        $result = $this->server_conn->query("SELECT count(*) as count FROM list_subscriber where status='unsubscribed'");
        $row = $result->fetch_assoc();
        $total_unsubscribers_count = $row['count'];
        return $total_unsubscribers_count;
    }

    public function totalBlacklistEmails() {
        //total blacklist emails count
        $result = $this->server_conn->query("SELECT\n"
                . " (SELECT COUNT(*) FROM email_blacklist) as emailblacklist, \n"
                . " (SELECT COUNT(*) FROM campaign_ip_bounce_log) as ipblacklist\n"
                . "");
        $row = $result->fetch_assoc();
        $blacklistbydefault = $row['emailblacklist'];
        $blacklistbyip = $row['ipblacklist'];
        $blacklist_total_count = ($blacklistbydefault + $blacklistbyip);
        return $blacklist_total_count;
    }

    public function totalBlacklist30Days() {
        //email blacklist last 30 days
        $result = $this->server_conn->query("SELECT count(*) as count FROM email_blacklist WHERE date_added BETWEEN (CURDATE( ) - INTERVAL 1 MONTH) AND CURDATE( )");
        $row = $result->fetch_assoc();
        $blacklist_30days_count = $row['count'];
        return $blacklist_30days_count;
    }

    public function ipBounceLog() {
        //campaign_ip_bounce_log
        $result = $this->server_conn->query("SELECT COUNT(*) as count FROM campaign_ip_bounce_log");
        $row = $result->fetch_assoc();
        $campaign_ip_bounce_log = $row['count'];
        return $campaign_ip_bounce_log;
    }

    public function emailblacklist7Days() {
        //email blacklist last 7 days
        $result = $this->server_conn->query("SELECT count(*) as count FROM email_blacklist WHERE date_added BETWEEN (CURDATE( ) - INTERVAL 7 DAY) AND CURDATE( )");
        $row = $result->fetch_assoc();
        $blacklist_7days_count = $row['count'];
        return $blacklist_7days_count;
    }

    public function todayEmailBlacklist() {
        //today email blacklist
        $result = $this->server_conn->query("SELECT count(*) as count FROM email_blacklist WHERE DATE(`date_added`) = CURDATE()");
        $row = $result->fetch_assoc();
        $blacklist_today_count = $row['count'];
        return $blacklist_today_count;
    }

    public function bounceLog30() {
        //bounced emails from last 30 days
        $result = $this->server_conn->query("SELECT count(*) as count FROM campaign_bounce_log WHERE date_added BETWEEN (CURDATE( ) - INTERVAL 1 MONTH) AND CURDATE( )");
        $row = $result->fetch_assoc();
        $bounceLog30 = $row['count'];
        return $bounceLog30;
    }

    public function totalDeliveryEmails() {
        //email last 7 days
        $result = $this->server_conn->query("SELECT count(*) as count FROM delivery_server_usage_log");
        $row = $result->fetch_assoc();
        $totalDeliveryEmails = $row['count'];
        return $totalDeliveryEmails;
    }

    public function totalDeliveryEmails30() {
        //email last 7 days
        $result = $this->server_conn->query("SELECT count(*) as count FROM delivery_server_usage_log WHERE date_added BETWEEN (CURDATE( ) - INTERVAL 1 MONTH) AND CURDATE( )");
        $row = $result->fetch_assoc();
        $totalDeliveryEmails30 = $row['count'];
        return $totalDeliveryEmails30;
    }

    public function totalDeliveryEmails7() {
        //email last 7 days
        $result = $this->server_conn->query("SELECT count(*) as count FROM delivery_server_usage_log WHERE date_added BETWEEN (CURDATE( ) - INTERVAL 7 DAY) AND CURDATE( )");
        $row = $result->fetch_assoc();
        $emails_7days_count = $row['count'];
        return $emails_7days_count;
    }

    public function totalDeliveryEmailsToday() {
        //today emails count
        $result = $this->server_conn->query("SELECT count(*) as count FROM delivery_server_usage_log WHERE date_added >= CURDATE()");
        $row = $result->fetch_assoc();
        $emails_today_count = $row['count'];
        return $emails_today_count;
    }

    public function ceoAndUsers($domain_name) {
        //updateing ceo_users emails
        $ceo_emails = '';
        $ceo_user_id = '';
        $client_emails = '';
        $result = $this->server_conn->query("select email from user");
        while ($row = $result->fetch_assoc()) {
            $ceo_emails[] = $row['email'];
        }
        $result = $this->server_conn->query("select email from customer");
        while ($row = $result->fetch_assoc()) {
            $client_emails[] = $row['email'];
        }

        $client_emails = implode(",", $client_emails);
        $ceo_emails = implode(",", $ceo_emails);
        $data = array(
            'client_emails' => $client_emails,
            'ceo_emails' => $ceo_emails
        );
        return $data;
    }
    
    //This method is getting login information about clients
    public function advancedData() {
        $data = '';
        $result = $this->advancedDataGetUsers();
        if (!$result->num_rows > 0) {
            return null;
        }
        while ($row = $result->fetch_assoc()) {
            $user_id = $row['user_id'];
            $result2 = $this->advancedDataGetCustomers($user_id);
            if (!$result2->num_rows > 0) {
                continue;
            }
            while ($row2 = $result2->fetch_assoc()) {
                $customer_id = $row2['customer_id'];
                $customer_email = $row2['email'];
                $result3 = $this->advancedDataGetEmailsUsageCount($customer_id);
                $row3 = $result3->fetch_assoc();
                $emailUsage = $row3['count'];
                if(empty($emailUsage)){
                    continue;
                }
                $data[] = array(
                    'customer_email' => $customer_email,
                    'emailUsage' => $emailUsage,
                );
            }
        }
        return json_encode($data);
    }

    public function advancedDataGetUsers(){
        $result = $this->server_conn->query("select * from user where status = 'active'");
        return $result;
    }
    public function advancedDataGetCustomers($user_id){
        $result2 = $this->server_conn->query("select * from customer where created_by = '$user_id' and status = 'active'");
        return $result2;
    }
    public function advancedDataGetEmailsUsageCount($customer_id){
        $result3 = $this->server_conn->query("select count(*) as count from delivery_server_usage_log where customer_id = '$customer_id'");
        return $result3;
    }
    
    

    public function customerActionLog() {
        $result = $this->server_conn->query("SELECT * FROM customer_action_log ORDER BY log_id DESC LIMIT 1;");
        $row = $result->fetch_assoc();
        $customer_id = $row['customer_id'];
        $message = $row['message'];
        $result = $this->server_conn->query("select * from customer where customer_id = '$customer_id'");
        $row = $result->fetch_assoc();
        $first_name = $row['first_name'];
        $last_name = $row['last_name'];
        $email = $row['email'];
        $customerActionLog = "$first_name $last_name $message Email : $email";
        $customerActionLog = $this->server_conn->real_escape_string($customerActionLog);
        return $customerActionLog;
    }

    public function emailValidationcron() {
        $result = $this->tracker_conn->query("select * from domains_info_note where note_type = 'EValidationDomain'");
        while ($row = $result->fetch_assoc()) {
            $validationDomains[] = $row['note_key'];
        }
        $result = $this->tracker_conn->query("select * from domains_info_note where note_type = 'EValidationEmail'");
        while ($row = $result->fetch_assoc()) {
            $validationEmails[] = $row['note_key'];
        }
        $date_of_added = $this->getTimeZone();
        foreach ($validationDomains as $validationDomain) {
            foreach ($validationEmails as $validationEmail) {
                $response = @file_get_contents("http://$validationDomain/EmailVerifyCron.php?email=$validationEmail");
                $response = $this->tracker_conn->real_escape_string($response);
                $this->tracker_conn->query("UPDATE domains_info_note SET 
            note_value= '$response', date_of_added = '$date_of_added' WHERE note_key = '$validationEmail' and note_type = 'EValidationEmail'");
            }
        }
    }

    public function emailRejectron($validationDomain) {
        $result = $this->tracker_conn->query("select * from domains_info_note where note_type = 'EValidationEmail'");
        while ($row = $result->fetch_assoc()) {
            $validationEmails[] = $row['note_key'];
        }
        $domain_reject = '';
        foreach ($validationEmails as $validationEmail) {
            $context = stream_context_create(array('http' => array('header' => 'Connection: close\r\n')));
            $response = @file_get_contents("http://$validationDomain/emailvalidation.php?email=$validationEmail", false, $context);
            $response = json_decode($response, true);
            if (empty($response['email_id'])) {
                return null;
            }
            $result = $response['result'];
            if ($result < 1) {
                $domain_reject[] = substr($response['email_id'], strpos($response['email_id'], "@") + 1);
            }
        }
        $domain_reject_string = @implode(", ", $domain_reject);
        $this->tracker_conn->query("UPDATE domains_info SET 
            emailReject= '$domain_reject_string' where domain_name = '$validationDomain'");
    }

    public function totalTransactionEmailsCount() {
        $result = $this->server_conn->query("SELECT count(*) as count FROM transactional_email WHERE status = 'sent'");
        $row = $result->fetch_assoc();
        $totalTransactionEmailsCount = $row['count'];
        return $totalTransactionEmailsCount;
    }

    public function totalTransactionEmailsCount30() {
        $result = $this->server_conn->query("SELECT count(*) as count FROM transactional_email WHERE send_at BETWEEN (CURDATE( ) - INTERVAL 1 MONTH) AND CURDATE( ) and status = 'sent'");
        $row = $result->fetch_assoc();
        $totalTransactionEmailsCount30 = $row['count'];
        return $totalTransactionEmailsCount30;
    }

    public function dnsbllookup($hostname) {
        $ip = gethostbyname($hostname);
        // Add your preferred list of DNSBL's
        $dnsbl_lookup = [
            "sbl.spamhaus.org",
            "pbl.spamhaus.org",
            "zen.spamhaus.org",
            "xbl.spamhaus.org",
            "truncate.gbudb.net",
            "all.s5h.net",
            "bl.spamcannibal.org",
            "bogons.cymru.com",
            "combined.abuse.ch",
            "dnsbl-2.uceprotect.net",
            "dnsbl.dronebl.org",
            "dul.dnsbl.sorbs.net",
            "http.dnsbl.sorbs.net",
            "korea.services.net",
            "orvedb.aupads.org",
            "psbl.surriel.com",
            "smtp.dnsbl.sorbs.net",
            "spam.dnsbl.anonmails.de",
            "spambot.bls.digibase.ca",
            "ubl.lashback.com",
            "web.dnsbl.sorbs.net",
            "z.mailspike.net",
            "b.barracudacentral.org",
            "bl.spamcop.net",
            "cbl.abuseat.org",
            "db.wpbl.info",
            "dnsbl-3.uceprotect.net",
            "dnsbl.inps.de",
            "drone.abuse.ch",
            "dyna.spamrats.com",
            "ips.backscatterer.org",
            "misc.dnsbl.sorbs.net",
            "relays.bl.gweep.ca",
            "short.rbl.jp",
            "socks.dnsbl.sorbs.net",
            "spam.dnsbl.sorbs.net",
            "spamrbl.imp.ch",
            "ubl.unsubscore.com",
            "wormrbl.imp.ch",
            "bl.emailbasura.org",
            "blacklist.woody.ch",
            "cdl.anti-spam.org.cn",
            "dnsbl-1.uceprotect.net",
            "dnsbl.anticaptcha.net",
            "dnsbl.sorbs.net",
            "duinv.aupads.org",
            "dynip.rothen.com",
            "ix.dnsbl.manitu.net",
            "noptr.spamrats.com",
            "proxy.bl.gweep.ca",
            "relays.nether.net",
            "singular.ttk.pte.hu",
            "spam.abuse.ch",
            "spam.spamrats.com",
            "spamsources.fabel.dk",
            "virus.rbl.jp",
            "zombie.dnsbl.sorbs.net"
        ];

        $dnsbl_lookup_domain = [
            "dbl.spamhaus.org"
        ];
        $listed = array();
        if ($ip) {
            $reverse_ip = implode(".", array_reverse(explode(".", $ip)));
            foreach ($dnsbl_lookup as $host) {
                if (checkdnsrr($reverse_ip . "." . $host . ".", "A")) {
                    $listed[] = $host;
                }
                if (checkdnsrr($hostname . "." . $host . ".", "A")) {
                    $listed[] = $host;
                }
            }
        }
        if ($hostname) {
            foreach ($dnsbl_lookup_domain as $host) {
                if (checkdnsrr($hostname . "." . $host . ".", "A")) {
                    $listed[] = $host;
                }
            }
        }

        if (empty($listed)) {
            return null;
        } else {
            $listed = implode(",", $listed);
            return $listed;
        }
    }

    public function reversedns($domain_name) {
        $ip = gethostbyname("$domain_name");
        $reverse_domain_name = gethostbyaddr($ip);
        $data = array(
            'rdns' => $reverse_domain_name,
            'ip' => $ip
        );
        return $data;
    }
    
    public function listsCount() {
        $result = $this->server_conn->query("SELECT count(*) as count FROM list WHERE status = 'active'");
        $row = $result->fetch_assoc();
        $listsCount = $row['count'];
        return $listsCount;
    }

    public function bandwidth($domain_name) {
        $context = stream_context_create(array('http' => array('header' => 'Connection: close\r\n')));
        $response = file_get_contents("http://$domain_name/status.txt", false, $context);
        return $response;
    }

    public function findAbuse($domain_name) {
        $context = stream_context_create(array('http' => array('header' => 'Connection: close\r\n')));
        $string = file_get_contents("http://$domain_name/mailq.php", false, $context);
        $pattern = '/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';
        $matchings = preg_match_all($pattern, $string, $matches);
        if (empty($matchings)) {
            $response = 1;
        } else {
            $get_email = $matches["0"]["0"];
            if (strpos($get_email, 'root') !== false) {
                $response = 1;
            } else {
                $result = $this->server_conn->query("SELECT email FROM `list_subscriber` WHERE `email` LIKE '%$get_email%'");
                $count = $result->num_rows;
                if (empty($count)) {
                    $response = 0;
                } else {
                    $response = 1;
                }
            }
        }
        $data = array(
            'mailq' => $this->server_conn->real_escape_string($string),
            'findabuse' => $this->server_conn->real_escape_string($response)
        );
        return $data;
    }

    public function getClientsTracking() {
        $result = $this->tracker_conn->query("select user_id,email,redirect from user");
        $get_client_not_exist = '';
        $get_ceo_not_found = '';
        while ($row = $result->fetch_assoc()) {
            $created_by = $row['user_id'];
            $ceo_email = $row['email'];
            $redirect = str_replace('http://', '', $row['redirect']);
            if (empty($redirect)) {
                continue;
            }
            //ceo redirect check
            $result3 = $this->tracker_conn->query("select COUNT(*) as count from domains_info where ceo_emails like '%$ceo_email%' and domain_name like '%%$redirect%%'");
            $row_ceo_count = $result3->fetch_assoc();
            $ceo_count = $row_ceo_count['count'];
            if (empty($ceo_count)) {
                $get_ceo_not_found[] = $ceo_email;
            }
            //ceo duplicate check 
            $result10 = $this->tracker_conn->query("select COUNT(*) as count from domains_info where ceo_emails like '%$ceo_email%'");
            $row_ceo_count10 = $result10->fetch_assoc();
            $ceo_count10 = $row_ceo_count10['count'];
            if ($ceo_count10 > 1) {
                $duplicate_ceo[] = $ceo_email;
            }
            // client redirect check 
            $result1 = $this->tracker_conn->query("select email,created_by from customer where created_by='$created_by'");
            if (empty($result1->num_rows)) {
                continue;
            }
            while ($row2 = $result1->fetch_assoc()) {
                $customer_email = $row2['email'];
                $result2 = $this->tracker_conn->query("select COUNT(*) as count from domains_info where client_emails like '%$customer_email%' and domain_name like '%$redirect%'");
                $row_for_count = $result2->fetch_assoc();
                $customer_count = $row_for_count['count'];
                if (empty($customer_count)) {
                    $get_client_not_exist[] = $customer_email;
                }
                //duplicate client check
                $result11 = $this->tracker_conn->query("select COUNT(*) as count from domains_info where client_emails like '%$customer_email%'");
                $row_for_count11 = $result11->fetch_assoc();
                $customer_count11 = $row_for_count11['count'];
                if ($customer_count11 > 1) {
                    $duplicate_client[] = $customer_email;
                }
            }            
        }
        //redirect fail update
        $notFoundClients = array(
            'user' => $get_ceo_not_found,
            'customer' => $get_client_not_exist
        );
        $fail_data = json_encode($notFoundClients);
        if (!$this->tracker_conn->query("UPDATE domains_info_note SET note_value = '$fail_data' where note_type='clientsFailRedirect'")) {
            var_dump($this->tracker_conn->error);
        }
        //duplicate found update 
        $duplicateFoundClients = array(
            'user' => $duplicate_ceo,
            'customer' => $duplicate_client
        );
        $duplicate_data = json_encode($duplicateFoundClients);
        if (!$this->tracker_conn->query("UPDATE domains_info_note SET note_value = '$duplicate_data' where note_type='duplicateClients'")) {
            var_dump($this->tracker_conn->error);
        }
    }

    public function updateTime() {
        $date = $this->getTimeZone();
        if (!$this->tracker_conn->query("UPDATE domains_info_note SET note_value = '$date' where note_key = 'AdvancedUpdateCron.php' and note_type='crons'")) {
            var_dump($this->tracker_conn->error);
        }
    }

    public function updateToTracker($domain_name, $data) {
        //final update
        extract($data);
        $result = $this->tracker_conn->query("UPDATE domains_info SET 
            bounce_server= '$bounseServers',
            delivery_server= '$deliveryServer',
            notexistcount= '$notExistEmails',
            unsubscribedcount= '$unsubscribeEmails',
            abuse= '$campaignAbuse',
            total_campaigns= '$allCampaigns',
            campaigns_30days= '$campaignsLast30Days',
            campaigns_7days= '$campaignsLast7Days',
            campaigns_today= '$campaignsToday',
            total_unsubscribed='$totalUnsubscribers',
            blacklist_total= '$totalBlacklistEmails',
            blacklist_30days= '$totalBlacklist30Days',
            blacklist_7days= '$emailblacklist7Days',
            blacklist_today= '$todayEmailBlacklist',
            total_emails = '$totalDeliveryEmails',
            listsCount = '$listsCount',
            bounce_log_30 = '$bounceLog30', 
            emails_30days = '$totalDeliveryEmails30',
            emails_7days='$totalDeliveryEmails7',
            emails_today='$totalDeliveryEmailsToday',
            totalTransactionEmailsCount ='$totalTransactionEmailsCount',
            totalTransactionEmailsCount30 = '$totalTransactionEmailsCount30',
            customerActionLog='$customerActionLog',
            ceo_emails= '$ceoAndUsers[ceo_emails]',
            client_emails = '$ceoAndUsers[client_emails]',
            advancedData = '$advancedData',
            campaign_ip_bounce_log='$ipBounceLog',
            domain_blacklist = '$dnsbllookup',
            reversedns = '$reversedns[rdns]',
            ip_address = '$reversedns[ip]',                
            findabuse =  '$findAbuse[findabuse]',
            mailq = '$findAbuse[mailq]',
            bandwidth = '$bandwidth'
	WHERE domain_name = '$domain_name'");
        if (!$result) {
            var_dump("$domain_name updateing domain failed" . $this->tracker_conn->error);
        } else {
            var_dump("$domain_name sucessfully updated");
        }
    }

    // update tracker process
    public function updateServers() {
        $get_domains = $this->getServers();
        foreach ($get_domains as $get_domain) {
            $this->server_conn = $this->connectServer($get_domain);
            if ($this->server_conn->connect_error) {
                unset($this->server_conn);
                continue;
            }
            $bounseServers = $this->bounseServers();
            $deliveryServer = $this->deliveryServer();
            $notExistEmails = $this->notExistEmails();
            $unsubscribeEmails = $this->unsubscribeEmails();
            $campaignAbuse = $this->campaignAbuse();
            $allCampaigns = $this->allCampaigns();
            $campaignsLast30Days = $this->campaignsLast30Days();
            $campaignsLast7Days = $this->campaignsLast7Days();
            $campaignsToday = $this->campaignsToday();
            $totalUnsubscribers = $this->totalUnsubscribers();
            $totalBlacklistEmails = $this->totalBlacklistEmails();
            $totalBlacklist30Days = $this->totalBlacklist30Days();
            $todayEmailBlacklist = $this->todayEmailBlacklist();
            $emailblacklist7Days = $this->emailblacklist7Days();
            $ipBounceLog = $this->ipBounceLog();
            $customerActionLog = $this->customerActionLog();
            $bounceLog30 = $this->bounceLog30();
            $listsCount = $this->listsCount();
            $totalDeliveryEmails = $this->totalDeliveryEmails();
            $totalTransactionEmailsCount = $this->totalTransactionEmailsCount();
            $totalTransactionEmailsCount30 = $this->totalTransactionEmailsCount30();
            $totalDeliveryEmails30 = $this->totalDeliveryEmails30();
            $totalDeliveryEmails7 = $this->totalDeliveryEmails7();
            $totalDeliveryEmailsToday = $this->totalDeliveryEmailsToday();
            $ceoAndUsers = $this->ceoAndUsers($get_domain['domain_name']);
            $dnsbllookup = $this->dnsbllookup($get_domain['domain_name']);
            $reversedns = $this->reversedns($get_domain['domain_name']);
            $bandwidth = $this->bandwidth($get_domain['domain_name']);
            $findAbuse = $this->findAbuse($get_domain['domain_name']);
            $this->emailRejectron($get_domain['domain_name']);
            $advancedData = $this->advancedData();
            $data = get_defined_vars();

            unset($data['get_domains']);

            $this->updateToTracker($get_domain['domain_name'], $data);

            unset($this->server_conn);
        }
        $this->updateTime();
        $this->emailValidationcron();
        $this->getClientsTracking();
    }

}

echo '<pre>';
$AdvancedUpdateCron = new AdvancedUpdateCron();
$AdvancedUpdateCron->updateServers();
