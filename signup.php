<?php
// a page for a sign up
require 'comm.php';
require 'db.php';

////////////////////////////
// check security issue
// 1. http only
// 2. session hijack
////////////////////////////

// for return json
$myres = [
  'signup' => 0,
	'msg' => 'signup fail'
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
// 1. check username is in use
phpLog($DEBUG, sprintf("signup.php, param, [%s][%s]", $myreq['signupUsername'], $myreq['signupNickname']));

$signupUsername = $myreq['signupUsername'];
$signupNickname = $myreq['signupNickname'];

// sanitize input value
$errorMsg = '';
if( checkInputEmail($signupUsername) != '') {
  $errorMsg = 'error: check Username, '.checkInputEmail($signupUsername);
}
else if( checkInputText($signupNickname) != '') {
  $errorMsg = 'error: check Nickname, '.checkInputText($signupNickname);
}

if($errorMsg != '') {
  $myres['msg'] = $errorMsg;
}
else {
  $user = dbGetUser($signupUsername);
  phpLog($DEBUG, sprintf("signup.php, user:[%s]",print_r($user,true)));
  // check the username is not in use
  if(isset($user['username']) && $user['username'] != ""){
    $myres['msg'] = 'Use a different username.';
  }
  else {
    $username = $signupUsername;
    $password = password_hash($myreq['signupPassword'], PASSWORD_DEFAULT);
    $nickname = $signupNickname;
  
    dbSetUser($username, $password, $nickname);
  
    $myres['signup'] = 1;
  }
}

header("Content-Type: application/json");
echo json_encode($myres);
?>