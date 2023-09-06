<?php
// a page for a logout 
require 'comm.php';

////////////////////////////
// check security issue
// 1. http only
// 2. session hijack
// 3. user session(check login)
// 4. CSRF token check
////////////////////////////

// for return json
$myres = [
	'logout' => 0,
	'msg' => 'logout fail'
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
	$myres['msg'] = 'logout fail';
	echo json_encode($myres);	
	exit;
}

////////////////////////////
// page logic begins
////////////////////////////
session_destroy();
// for return json
$myres['logout'] = 1;
$myres['msg'] = 'Login please';


header("Content-Type: application/json");
echo json_encode($myres);
?>