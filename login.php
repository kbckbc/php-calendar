<?php
// a page for a login 
// if fails, return {logged:0}
// else {logged:1, username:User's name(email)}
require 'comm.php';
require 'db.php';

////////////////////////////
// check security issue
// 1. http only
// 2. session hijack
////////////////////////////

// for return json
$myres = [
  'logged' => 0,
  'msg' => 'Login please'
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

// when login page is requested without param
// look up a session for logged user and return it
if(count($myreq) == 0) {
  if(!isset($_SESSION['username'])) {
    phpLog($DEBUG, 'login.php, no param 1, not logged in');
  }
  else {
    phpLog($DEBUG, 'login.php, no param 2, already logged in as:'.$_SESSION['username']);
  
    $myres['logged'] = 1;
    $myres['token'] = $_SESSION['token'];
    $myres['msg'] = $_SESSION['nickname'].'('.$_SESSION['username'].')';
  }
}
// when requested with params
// do a login process
else {
  $loginUsername = $myreq['loginUsername'];
  $loginPassword = $myreq['loginPassword'];
    
  $user = dbGetUser($loginUsername);  

  // when username does not exist
  if(!isset($user['username'])){
    phpLog($DEBUG, 'login.php, with param 1, username not match:'.$loginUsername);
    $myres['msg'] = 'username or password does not match';
  }    
  else {
    phpLog($DEBUG, 'login.php, with param 2, username match:'.$loginUsername);

    $password = $user['password'];
    
    // when password does not match
    if(!password_verify($loginPassword, $password)){
      phpLog($DEBUG, 'login.php, with param 3, password not match:'.$loginUsername);
      phpLog($DEBUG, 'login.php, with param 3, pass1:'.$loginPassword);
      phpLog($DEBUG, 'login.php, with param 3, pass2:'.$password);

      $myres['msg'] = 'username or password does not match';
    } 
    else {
      phpLog($DEBUG, 'login.php, with param 4, password match:'.$loginUsername);
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['nickname'] = $user['nickname'];
      $_SESSION['token'] = bin2hex(random_bytes(32));
      
      $myres['logged'] = 1;
      $myres['token'] = $_SESSION['token'];
      $myres['msg'] = $user['nickname'].'('.$user['username'].')';
    }
  }
}


header("Content-Type: application/json");
echo json_encode($myres);
?>