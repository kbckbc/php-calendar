<?php
// a page for constant values and common functions
// global constant variables
$DEBUG = false;

// print out log in a php log file
function phpLog($debug, $log) {
    if($debug == true) {
        error_log('====================> '.$log, 0);
    }
}

// check input user name validation
function checkInputText($input) {
    $msg = "";
    if (!preg_match("/^[\w\s]+$/",$input)) {
        $msg = "Only letters and white space allowed";
    }    
    return $msg;
}

// check input user name validation
function checkInputEmail($input) {
    $msg = "";
    if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email format";
    }    
    return $msg;
}
