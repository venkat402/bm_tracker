<?php

$g_hostname = 'technohrmmail.info';
$g_db_type = 'mysqli';
$g_database_name = 'zadmin_emails';
$g_db_username = 'root';
$g_db_password = 'QFTOCJg1QwsB4BaC';

$g_default_timezone = 'Asia/Kolkata';

$g_crypto_master_salt = 'My05Q8Mpb845g3ntgUVtm/9sxwYJjT2UC+vCsCLYTL0=';
$g_enable_email_notification = OFF;
$g_session_validation = OFF;
$g_bottom_include_page = 'test.php';


//$g_allow_signup = ON;
//$g_allow_anonymous_login = OFF;
//$g_anonymous_account = '';

#--- Branding ---
$g_window_title = 'BulkMailBugsTracker';
$g_logo_image = 'images/technohrm.jpg';
$g_favicon_image = 'images/favicon.ico';

//date format 
$g_short_date_format    = 'd-M-y h:i A';
$g_normal_date_format   = 'd-M-y h:i A';
$g_complete_date_format = 'd-M-y h:i A';

//session time out
$g_reauthentication_expiry = 10*60*60*60*60*60*60*60*60; 


//disable header
$g_top_include_page = '';



//display errors
$g_display_errors = array(
	E_USER_WARNING => DISPLAY_ERROR_HALT,
	E_WARNING      => DISPLAY_ERROR_HALT,
	E_ALL          => DISPLAY_ERROR_INLINE,
);





//$g_form_security_validaton = OFF;
$g_form_security_validation = OFF;

$set_project_private_threshold = ON;

//allow to view private issues 
$g_private_bug_threshold = DEVELOPER;
$g_private_bugnote_threshold = DEVELOPER;

$g_move_bug_threshold = NOBODY;
$g_set_bug_sticky_threshold = NOBODY;

        