<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class MoveClientsCron {

    public $tracker_conn;
    public $new_conn;
    public $old_conn;

    public function trackerDbConnect() {
        if (!empty($this->tracker_conn)) {
            return $this->tracker_conn;
        }
        require_once ( dirname(__DIR__) . '/connect.php');
        $db = new Connect();
        $this->tracker_conn = $db->conn();
    }

    public function getTimeZone() {
        date_default_timezone_set('Asia/Calcutta');
        $ist_time = date('d/m/Y h:i:s A');
        return $ist_time;
    }

    public function getMoveClientRecord() {
        $result = $this->tracker_conn->query("select * from moveceo where message LIKE '%added successfully%' LIMIT 1");
        if ($this->tracker_conn->error) {
            var_dump($this->tracker_conn->error);
        }
        $row = $result->fetch_assoc();
        return $row;
    }

    public function moveClientErrorLog($errorLog, $ceoData) {
        $id = $ceoData['id'];
        $result = $this->tracker_conn->query("UPDATE moveceo SET message='$errorLog' WHERE id='$id'");
        die;
    }

    public function moveCeoSetSuccessMessage($succMsg, $ceoData) {
        $id = $ceoData['id'];
        $this->tracker_conn->query("UPDATE moveceo SET message='$succMsg' WHERE id = '$id'");
    }

    public function fromServer($get_domain) {
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

    public function toServer($get_domain) {
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

    //random string provider
    public function generateRandomString($length = 13) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getCredentialsOfSingleServer($fromDb) {
        $this->trackerDbConnect();
        $result = $this->tracker_conn->query("SELECT domain_name,user_name,domain_password,db_name FROM domains_info where domain_name = '$fromDb'");
        $get_domain = $result->fetch_assoc();
        return $get_domain;
    }

    public function fromDb($ceoData) {
        $fromDbName = $ceoData['fromDb'];
        $fromDb = $this->fromServer($this->getCredentialsOfSingleServer($fromDbName));
        if (!$fromDb) {
            var_dump($fromDb->connect_error);
            $this->moveClientErrorLog("From Db Failed to connect", $ceoData);
        } else {
            $this->old_conn = $fromDb;
        }
    }

    public function toDb($ceoData) {
        $toDbName = $ceoData['toDb'];
        $toDb = $this->toServer($this->getCredentialsOfSingleServer($toDbName));
        if (!$toDb) {
            var_dump($fromDb->connect_error);
            $this->moveClientErrorLog("To Db Failed to connect", $ceoData);
        } else {
            $this->new_conn = $toDb;
        }
    }

    public function moveCompleted($ceoData) {
        $id = $ceoData['id'];
        $result = $this->tracker_conn->query("UPDATE moveceo SET status='success',message='Moved successfully.' WHERE id = '$id'");
        if ($result) {
            echo "status successfully updated in moveceo table";
        } else {
            echo "failed to update status in moveceo";
        }
    }

    public function changeRedirection($ceoData) {
        $ceoEmail = $ceoData['ceoEmail'];
        $checkagain = $this->tracker_conn->query("select * from user where email = '$ceoEmail'");
        if ($checkagain->num_rows > 0) {
            $toDBbName = $ceoData['toDb'];
            $toDb = "http://$toDBbName";
            $result = $this->tracker_conn->query("UPDATE user SET redirect='$toDb' WHERE email = '$ceoEmail'");
            if ($result) {
                echo "redirect update success in technohrmmail.info";
            } else {
                echo "redirect update failed in technohrmmail.info";
            }
        }
    }

    // move user
    public function moveUser($ceoData) {
        $ceoEmail = $ceoData['ceoEmail'];
        $this->moveCeoSetSuccessMessage('Processing ....', $ceoData);
        $user_id_result = $this->old_conn->query("select user_id,user_uid,group_id,first_name,last_name,email,password,timezone,status,customer_count,customer_mails,role,hiremail_id,company_name,redirect,subscribers_limit,date_added,last_updated from user where email = '$ceoEmail'");
        if (!$user_id_result->num_rows > 0) {
            $this->moveClientErrorLog("Ceo email not exist in from server", $ceoData);
        }
        $user_id_fetch = $user_id_result->fetch_assoc();
        $old_user_id = $user_id_fetch['user_id'];
        $removes = array('user_id', 'language_id', 'avatar', 'removable', 'customer_group_id');
        foreach ($removes as $remove) {
            unset($user_id_fetch[$remove]);
        }
//        $user_id_fetch['user_uid'] = $this->generateRandomString();
        $matstring = "'" . implode("','", array_map(array($this->old_conn, "real_escape_string"), $user_id_fetch)) . "'";
        //move user to new db 
        $result = $this->new_conn->query("select * from user where email = '$ceoEmail'");
        if (!$result->num_rows > 0) {
            $result = $this->new_conn->query("INSERT INTO user(user_uid,group_id,first_name,last_name,email,password,timezone,status,customer_count,customer_mails,role,hiremail_id,company_name,redirect,subscribers_limit,date_added,last_updated)
                            VALUES($matstring)");
        } else {
            $this->moveClientErrorLog("Ceo email already exist in to server", $ceoData);
        }
        $new_user_id = $this->new_conn->insert_id;
        $this->moveCeoSetSuccessMessage('Ceo moving ....', $ceoData);
        $data = array(
            'old_user_id' => $old_user_id,
            'new_user_id' => $new_user_id
        );
        return $data;
    }

    //move customer company
    public function moveCustomerCompany($ceoData, $data) {
        $oldCustomerIdValue = $data['old_customer_id'];
        $old_customer_company_query = $this->old_conn->query("select customer_id,country_id,name,address_1,city,zip_code,date_added,last_updated from customer_company where customer_id = '$oldCustomerIdValue'");
        if (!$old_customer_company_query->num_rows > 0) {
            return null;
        }
        $old_customer_company_result = $old_customer_company_query->fetch_assoc();
        $old_customer_company_result['customer_id'] = $data['new_customer_id'];
        $removes = array('company_id', 'type_id', 'zone_id', 'website', 'address_2', 'zone_name', 'phone', 'fax', 'vat_number');
        foreach ($removes as $remove) {
            unset($old_customer_company_result[$remove]);
        }
        $old_customer_company_result = array_map(array($this->old_conn, "real_escape_string"), $old_customer_company_result);
        $matstring = "'" . implode("','", $old_customer_company_result) . "'";
        $move_cusotmer_company = $this->new_conn->query("INSERT INTO customer_company(customer_id,country_id,name,address_1,city,zip_code,date_added,last_updated)
                    VALUES($matstring)");
        if (!$move_cusotmer_company) {
            echo "step5:customer company inserted fail";
        } else {
            echo "step5:customer company inserted success";
        }
    }

    //move list_company
    public function moveListCompany($ceoData, $data) {
        $oldListIdValue = $data['old_list_id'];
        $old_list_company_query = $this->old_conn->query("select list_id,country_id,name,address_1,city,zip_code from list_company where list_id = '$oldListIdValue'");
        if (!$old_list_company_query->num_rows > 0) {
            return null;
        }
        $old_list_company_result = $old_list_company_query->fetch_assoc();
        $old_list_company_result['list_id'] = $data['new_list_id'];
        $removes = array('type_id', 'zone_id', 'website', 'address_2', 'zone_name', 'phone', 'address_format');
        foreach ($removes as $remove) {
            unset($old_list_company_result[$remove]);
        }
        $old_list_company_result = array_map(array($this->old_conn, "real_escape_string"), $old_list_company_result);
        $matstring = "'" . implode("','", $old_list_company_result) . "'";
        $move_list_company_query = $this->new_conn->query("INSERT INTO list_company(list_id,country_id,name,address_1,city,zip_code)
        VALUES($matstring)");
        if (!$move_list_company_query) {
            var_dump("step7:list company insert fail");
        } else {
            var_dump("step7:list company insert success");
        }
    }

    //move list_default
    public function moveListDefault($ceoData, $data) {
        $oldListIdValue = $data['old_list_id'];
        $old_list_default_query = $this->old_conn->query("select list_id,from_name,from_email,reply_to from list_default where list_id = '$oldListIdValue'");
        if (!$old_list_default_query->num_rows > 0) {
            return null;
        }
        $old_list_default_result = $old_list_default_query->fetch_assoc();
        $old_list_default_result['list_id'] = $data['new_list_id'];
        unset($old_list_default_result['subject']);
        $old_list_default_result = array_map(array($this->old_conn, "real_escape_string"), $old_list_default_result);
        $matstring = "'" . implode("','", $old_list_default_result) . "'";
        $move_list_default_query = $this->new_conn->query("INSERT INTO list_default(list_id,from_name,from_email,reply_to)
        VALUES($matstring)");
        if (!$move_list_default_query) {
            echo "step7:list default insert fail";
        } else {
            echo "step7:list default insert success";
        }
    }

    //move list_field
    public function moveListField($ceoData, $data) {
        $oldListIdValue = $data['old_list_id'];
        $old_list_field_query = $this->old_conn->query("select type_id,list_id,label,tag,required,visibility,sort_order,date_added,last_updated from list_field where list_id like '$oldListIdValue'");
        if (!$old_list_field_query->num_rows > 0) {
            return null;
        }
        while ($old_list_field_result = $old_list_field_query->fetch_assoc()) {
            unset($old_list_field_result['field_id']);
            unset($old_list_field_result['default_value']);
            unset($old_list_field_result['help_text']);
            $old_list_field_result['list_id'] = $data['new_list_id'];
            $old_list_field_result = array_map(array($this->old_conn, "real_escape_string"), $old_list_field_result);
            $matstring = "'" . implode("','", $old_list_field_result) . "'";
            $move_list_field_query = $this->new_conn->query("insert into list_field(type_id,list_id,label,tag,required,visibility,sort_order,date_added,last_updated)
            VALUES($matstring)");

            if (!$move_list_field_query) {
                var_dump($this->new_conn->error);
                $this->moveClientErrorLog("List field insert fail", $ceoData);
            } else {
                echo "step7:list field insert success";
            }
        }
    }

    //move list_subscriber
    public function moveListSubscriber($ceoData, $data) {
        $oldListIdValue = $data['old_list_id'];
        $old_list_subscriber_query = $this->old_conn->query("select subscriber_uid,list_id,email,status,date_added,last_updated from list_subscriber where list_id like '$oldListIdValue'");
        if (!$old_list_subscriber_query->num_rows > 0) {
            return null;
        }
        while ($old_list_subscriber_result = $old_list_subscriber_query->fetch_assoc()) {
            $old_list_subscriber_email = $old_list_subscriber_result['email'];
//            $old_list_subscriber_result['subscriber_uid'] = $this->generateRandomString();
            unset($old_list_subscriber_result['subscriber_id']);
            unset($old_list_subscriber_result['ip_address']);
            unset($old_list_subscriber_result['source']);
            $old_list_subscriber_result['list_id'] = $data['new_list_id'];
            $old_list_subscriber_result = array_map(array($this->old_conn, "real_escape_string"), $old_list_subscriber_result);
            $matstrings[] = "('" . implode("','", $old_list_subscriber_result) . "')";
        }
        $move_list_query = $this->new_conn->query("INSERT INTO list_subscriber(subscriber_uid,list_id,email,status,date_added,last_updated)
            VALUES " . implode(",", $matstrings));

        if (!$move_list_query) {
            var_dump($this->new_conn->error);
            $this->moveClientErrorLog("List subscribers insert fail", $ceoData);
        } else {
            var_dump("List email inserted success");
        }
        $newListIdValue = $data['new_list_id'];
        $get_list_field_query = $this->new_conn->query("select field_id from list_field where list_id = '$newListIdValue'");
        $new_list_field_id_result = $get_list_field_query->fetch_assoc();
        $new_list_field_id = $this->new_conn->real_escape_string($new_list_field_id_result['field_id']);
        $new_list_subscriber_query = $this->new_conn->query("select subscriber_id,email from list_subscriber where list_id = '$newListIdValue'");
        if (!$new_list_subscriber_query->num_rows > 0) {
            return null;
        }
        while ($row = $new_list_subscriber_query->fetch_assoc()) {
            $row = array_map(array($this->new_conn, "real_escape_string"), $row);
            $subscriberIdValue = $row['subscriber_id'];
            $subscriberEmailValue = $row['email'];
            $newListFieldValues[] = "('" . $new_list_field_id . "','" . $subscriberIdValue . "','" . $subscriberEmailValue . "')";
        }

        $insert_into_new_list_field_value_query = $this->new_conn->query("INSERT INTO list_field_value(field_id,subscriber_id,value)
				VALUES " . implode(",", $newListFieldValues));

        if (!$insert_into_new_list_field_value_query) {
            var_dump($this->new_conn->error);
            $this->moveClientErrorLog("List field value insert fail", $ceoData);
        } else {
            echo "Email inserted last step done";
        }
    }

    //move list
    public function moveListData($ceoData, $data) {
        $oldCustomerIdValue = $data['old_customer_id'];
        $old_list_query = $this->old_conn->query("select list_id,list_uid,customer_id,name,display_name,description,visibility,welcome_email,removable,status,date_added,last_updated from list where customer_id = '$oldCustomerIdValue'");
        if (!$old_list_query->num_rows > 0) {
            return null;
        }
        $newCustomerIdValue = $data['new_customer_id'];
        while ($old_list_id_result = $old_list_query->fetch_assoc()) {
            //getting all old list data
            $old_list_id = $old_list_id_result['list_id'];
            $old_list_id_result['customer_id'] = $newCustomerIdValue;
            $removes = array('list_id', 'opt_in', 'opt_out', 'merged', 'subscriber_require_approval', 'subscriber_404_redirect', 'subscriber_exists_redirect', 'meta_data');
            foreach ($removes as $remove) {
                unset($old_list_id_result[$remove]);
            }
//            $old_list_id_result['list_uid'] = $this->generateRandomString();
            $old_list_id_result = array_map(array($this->new_conn, "real_escape_string"), $old_list_id_result);
            $matstring = "'" . implode("','", $old_list_id_result) . "'";
            $list_result = $this->new_conn->query("INSERT INTO list(list_uid,customer_id,name,display_name,description,visibility,welcome_email,removable,status,date_added,last_updated)
                    VALUES($matstring)");
            $new_list_id_2 = $this->new_conn->insert_id;
            $data['new_list_id'] = $new_list_id_2;
            $data['old_list_id'] = $old_list_id;
            if (!$list_result) {
                var_dump($this->new_conn->error);
                $this->moveClientErrorLog("List name insert fails", $ceoData);
            } else {
                var_dump("List insert successfull");
            }
            $this->moveListCompany($ceoData, $data);
            $this->moveListDefault($ceoData, $data);
            $this->moveListField($ceoData, $data);
            $this->moveListSubscriber($ceoData, $data);
        }
    }

    //deleteing the ceo from old db
    public function deleteCeo($ceoData) {
        $ceoEmail = $ceoData['ceoEmail'];
        $user_query = $this->old_conn->query("select user_id from user where email = '$ceoEmail'");
        $user_result = $user_query->fetch_assoc();
        $user_id = $user_result['user_id'];
        $customer_select_query = $this->old_conn->query("select email,customer_id from customer where created_by = '$user_id'");
        if (!$customer_select_query->num_rows > 0) {
            return null;
        }
        while ($customer_select_result = $customer_select_query->fetch_assoc()) {
            $customer_email = $customer_select_result['email'];
            $customer_id = $customer_select_result['customer_id'];
            $this->old_conn->query("delete from customer_action_log where customer_id = '$customer_id'");
            $this->old_conn->query("delete from customer_auto_login_tocken where customer_id = '$customer_id'");
            $this->old_conn->query("delete from customer_company where customer_id = '$customer_id'");
            $this->old_conn->query("delete from customer_quota_mark where customer_id = '$customer_id'");
            $list_id_query = $this->old_conn->query("select list_id from list where customer_id = '$customer_id'");
            if (!$list_id_query->num_rows > 0) {
                continue;
            }
            while ($list_id_result = $list_id_query->fetch_assoc()) {
                $get_list_id = $list_id_result['list_id'];
                $this->old_conn->query("delete from list_company where list_id = '$get_list_id'");
                $this->old_conn->query("delete from list_customer_notification where list_id = '$get_list_id'");
                $this->old_conn->query("delete from list_default where list_id = '$get_list_id'");
                $this->old_conn->query("delete from list_subscriber where list_id = '$get_list_id'");
                $this->old_conn->query("select field_id from list_field where list_id = '$get_list_id'");
                while ($field_id_result = $field_id->fetch_assoc()) {
                    $get_field_id = $field_id_result['field_id'];
                    $delete_list_field_value = $this->old_conn->query("delete from list_field_value where field_id = '$get_field_id'");
                }
                $this->old_conn->query("delete from list_field where list_id = '$get_list_id'");
            }
            $campaign_id = $this->old_conn->query("select campaign_id from campaign where customer_id = '$customer_id'");
            if (!$campaign_id->num_rows > 0) {
                continue;
            }
            while ($campaign_id_result = $campaign_id->fetch_assoc()) {
                $get_campaign_id = $campaign_id_result['campaign_id'];
                $this->old_conn->query("delete from campaign_option where campaign_id = '$get_campaign_id'");
            }
            $this->old_conn->query("delete from campaign where customer_id = '$customer_id'");
            $this->old_conn->query("delete from list where customer_id = '$customer_id'");
            $this->old_conn->query("delete from customer where email = '$customer_email'");
        }
        $this->old_conn->query("delete from user where email = '$ceoEmail'");
    }

    public function moveCustomer($ceoData, $data) {
        $oldUserIdValue = $data['old_user_id'];
        $old_customer_query = $this->old_conn->query("select customer_id,customer_uid,first_name,last_name,email,password,timezone,removable,confirmation_key,status,created_by,mails_quota,subscribers_quota,date_added,last_updated from customer where created_by = '$oldUserIdValue'");
        if (!$old_customer_query->num_rows > 0) {
            $this->moveCompleted($ceoData);
        }
        $count = $old_customer_query->num_rows;
        $sNo = 1;
        while ($old_customer_result = $old_customer_query->fetch_assoc()) {
            $new_user_id = $data['new_user_id'];
            $old_customer_id = $old_customer_result['customer_id'];
            $old_customer_email = $old_customer_result['email'];
            unset($old_customer_result['customer_id'], $old_customer_result['group_id'], $old_customer_result['language_id'], $old_customer_result['avatar'], $old_customer_result['hourly_quota'], $old_customer_result['oauth_uid'], $old_customer_result['oauth_provider'], $old_customer_result['credits'], $old_customer_result['refer_by'], $matstring);
            $old_customer_result['created_by'] = $new_user_id;
//            $old_customer_result['customer_uid'] = $this->generateRandomString();
            $matstring = "'" . implode("','", array_map(array($this->new_conn, "real_escape_string"), $old_customer_result)) . "'";
            //move customer one by one			
            $query = $this->new_conn->query("select * from customer where email = '$old_customer_email'");
            if ($query->num_rows > 0) {
                continue;
            }
            $move_cusotmer = $this->new_conn->query("INSERT INTO customer(customer_uid,first_name,last_name,email,password,timezone,removable,confirmation_key,status,created_by,mails_quota,subscribers_quota,date_added,last_updated)
                                    VALUES($matstring)");
            $new_customer_id_2 = $this->new_conn->insert_id;
            $data['new_customer_id'] = $new_customer_id_2;
            $data['old_customer_id'] = $old_customer_id;
            $data['old_customer_email'] = $old_customer_email;

            if (!$move_cusotmer) {
                var_dump($this->new_conn->error);
                $this->moveClientErrorLog('Insert customer failed', $ceoData);
            } else {
                $this->moveCeoSetSuccessMessage("($sNo/$count) $old_customer_email processing ..... ", $ceoData);
            }
            $this->moveCustomerCompany($ceoData, $data);
            $this->moveListData($ceoData, $data);

            $sNo++;
        }
    }

    public function updateTime() {
        $date = $this->getTimeZone();
        if (!$this->tracker_conn->query("UPDATE domains_info_note SET note_value = '$date' where note_key = 'MoveClientsCron.php' and note_type='crons'")) {
            var_dump($this->tracker_conn->error);
        }
    }

    public function updateServers() {
        $this->trackerDbConnect();
        $ceoData = $this->getMoveClientRecord();
        $this->updateTime();
        if (empty($ceoData)) {
            die('No move client records found');
        }
        $this->fromDb($ceoData);
        $this->toDb($ceoData);
        $userData = $this->moveUser($ceoData);
        $this->moveCustomer($ceoData, $userData);
        $this->moveCompleted($ceoData);
        $this->changeRedirection($ceoData);
        //$this->deleteCeo($ceoData);
    }

}

echo '<pre>';
$MoveClientsCron = new MoveClientsCron();
$MoveClientsCron->updateServers();
