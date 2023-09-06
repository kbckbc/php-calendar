<?php
// a page for selecting sharing info
require 'comm.php';
require 'db.php';

////////////////////////////
// check security issue
// 1. http only
// 2. session hijack
// 3. user session(check login)
////////////////////////////

// for return json
$myres = [
	'read' => 0,
	'msg' => 'sharing read fail'
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
		
////////////////////////////
// page logic begins
////////////////////////////
phpLog($DEBUG,sprintf("s_read_sharing.php, no param"));

if( $res = dbGetSharing($_SESSION['user_id']) ) {
	phpLog($DEBUG,sprintf("s_add_sharing.php, dbGetSharing: [%d]", count($res)));
	$myres['read'] = 1;
	$myres['msg'] = 'sharing read succ';
	$myres['sharing'] = $res;
}

header("Content-Type: application/json");
echo json_encode($myres);
?>