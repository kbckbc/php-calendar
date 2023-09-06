<?php
// a page for viewing a story
// it can have edit or delete button and viewing comments as well
// a page for a sign up
require 'comm.php';
require 'db.php';

////////////////////////////
// check security issue
// 1. http only
// 2. session hijack
// 3. user session(check login)
// 4. CSRF token check
////////////////////////////

// for return json
$myres = [
	'add' => 0,
	'msg' => 'sharing add fail'
];

// Set httponly 
ini_set("session.cookie_httponly", 1);
session_start();

// check session hijack 
$previous_ua = @$_SESSION['useragent'];
$current_ua = $_SERVER['HTTP_USER_AGENT'];

if(isset($_SESSION['useragent']) && $previous_ua !== $current_ua){
	$myres['msg'] = 'Session hijack detected';
	echo json_encode($myres);	
	exit;
}else{
	$_SESSION['useragent'] = $current_ua;
}

// SESSION check(whether logged in or not)
if(!isset($_SESSION['user_id'])) {
	$myres['msg'] = 'Login please';
	echo json_encode($myres);	
	exit;
}

// Read the input stream
// Decode the JSON object
$body = file_get_contents("php://input");
$myreq = json_decode($body, true);
if (!is_array($myreq)) {
	$myres['msg'] = 'Failed to decode JSON object';
	echo json_encode($myres);	
	exit;  
}
	
// CSRF token check
if(!hash_equals($_SESSION['token'], $myreq['token'])){
	$myres['msg'] = 'Request forgery detected';
	echo json_encode($myres);	
	exit;  
}

////////////////////////////
// page logic begins
////////////////////////////
phpLog($DEBUG,sprintf("s_add_sharing.php, param [%s]", $myreq['taker_id']));

if( $res = dbAddSharing($myreq['taker_id']) ) {
	phpLog($DEBUG,sprintf("s_add_sharing.php, dbAddSharing: [%s]", $res));
	$myres['add'] = 1;
	$myres['msg'] = 'sharing add succ';
}
	
header("Content-Type: application/json");
echo json_encode($myres);
?>