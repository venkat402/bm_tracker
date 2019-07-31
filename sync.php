<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$data['hiremail'] = array(
    'class' => 'CDbConnection',
    'connectionString' => 'mysql:host=technohrmmail.info;dbname=zadmin_emails',
    'username' => 'root',
    'password' => 'QFTOCJg1QwsB4BaC',
    'tablePrefix' => '',
);
echo base64_encode(json_encode($data));
