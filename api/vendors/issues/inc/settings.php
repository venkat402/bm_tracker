<?php

if (!defined('APPLICATION_LOADED') || !APPLICATION_LOADED) {
    die('No direct script access.');
}
//Debug mode show every sql query on pages
define('DEBUG_MODE', FALSE);

//Set default timezone 
date_default_timezone_set('Asia/Kolkata');

//ALLOWED CHARS FOR PASS GEN AND PASS RESET
define('ALLOWED_CHARS_GEN', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

//This is type of date that are in dashboard and ticket view
define('TICKETS_DATE_TYPE', 'd/M/Y h:i A');
define('TICKETS_DUEDATE_TYPE', 'd/M/Y');
define('TRACKTIME_CREATE_DATE_TYPE', 'd/M/Y');
define('ESTIMATED_TIME_TYPE', '%a days, %h hours and %i minutes');
define('ACTIVITY_DATE_TYPE', 'd/M/Y h:i A');

//settings page
define('MAX_NUM_NOTIF', 999); //max number notifications like integer on menu
define('MAX_NUM_IN_BRECKETS', 999); // same but in statistics
define('RESULT_LIMIT_SETTINGS_PROFILES', 20);
define('RESULT_LIMIT_SETTINGS_SPACES', 20);
define('SETTINGS_PROFILES_DATETYPE', 'd/M/Y h:i A');
define('PROJECTS_TIME_CREATED', 'd/M/Y h:i A');

define('SPACES_TIME_CREATED', 'd/M/Y h:i A');

//profile page
define('RESULT_LIMIT_PROFILES', 20);

//pages
define('PAGES_UPDATE_TYPE_DATE', 'M d, Y');

//email notifications
define('EMAIL_SUBJECT', 'Notification alert!');
define('EMAIL_MESSAGE', 'You have new notification from ticket system!');
?>
