<?php
// a page for managing all queries on db
require '../../dev/m5g/connect.php';

////////////////////////////
// manage users
////////////////////////////
// get user info from db
// input: username 
// return: if user is in db, return the user info array, or return null
function dbGetUser($name) {
    global $DEBUG;

    $result = array();
    $sql = connDb();

    // Use a prepared statement
    $query = "SELECT user_id, username, password, nickname, ctime FROM users WHERE username=?";
    $stmt = $sql->prepare($query);
	if(!$stmt){
        phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
        exit;
	}

    // Bind the parameter and execute
    $stmt->bind_param('s', $name);
    $stmt->execute();
	$res = $stmt->get_result();
	$stmt->close();

    if($res->num_rows) {
        $row = $res->fetch_assoc();
        $result['user_id'] = $row['user_id'];
        $result['username'] = $row['username'];
        $result['password'] = $row['password'];
        $result['nickname'] = $row['nickname'];
        $result['ctime'] = $row['ctime'];
    }

    phpLog($DEBUG, sprintf("dbGetUser(), count:[%d]", $res->num_rows));
    phpLog($DEBUG, sprintf("dbGetUser(), result:[%s]", print_r($result,true)));
        
    return $result;
}

// insert a new user into a db. 
// input: user info
// return: if succ return true, or exit
function dbSetUser($username, $password, $nickname) {
    global $DEBUG;

    $sql = connDb();

    $stmt = $sql->prepare("insert into users (username, password, nickname, ctime)  values (?, ?, ?, now())");
    if(!$stmt){
        phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
        exit;
    }

    $stmt->bind_param('sss', $username, $password, $nickname);
    $stmt->execute();
    $stmt->close();

    return true;
}

////////////////////////////
// manage events
////////////////////////////
// insert or update an event
// input : title, content, wtime, event_id: if event_id was given, it will update the event
// return: true if insert or update succedd or return false
function dbSetEvent($title, $content, $day, $time, $event_id = '') {
    global $DEBUG;

    $day = substr($day, 0, 4).'-'.substr($day,4,2).'-'.substr($day,6,2);

    // before proceed, check session user_id
    if(!isset($_SESSION['user_id'])) {
        phpLog($DEBUG, sprintf("dbSetEvent(), login is needed"));
        return false;
    }
    else {
        $sql = connDb();

        // update if event_id is given
        if($event_id != '') {
            // check event's user_id is the same with session's value
            $stmt = $sql->prepare("select user_id from events where event_id = ?");
            if(!$stmt){
                phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
                exit;
            }
            $stmt->bind_param('d', $event_id);
            $stmt->execute();
            $res = $stmt->get_result();
    
            if($res->num_rows) {
                $row = $res->fetch_assoc();
                $result = $row;
            }
        
            if($result['user_id'] != $_SESSION['user_id']) {
                phpLog($DEBUG, sprintf("dbSetEvent(), update, the same account can update.request id[%d] session id[%d]",$result['user_id'],$_SESSION['user_id']));
                return false;
            }
    
            // if the request account and session id is the same, do a update
            $stmt = $sql->prepare("update events set title=?, content=?, wtime=? where event_id=?");
            if(!$stmt){
                phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
                exit;
            }
            
            $newTime = $day.' '.$time;
            
            phpLog($DEBUG, sprintf("dbSetEvent(), update, bind_param:[%d][%s][%s][%s][%d]", $_SESSION['user_id'], $title, $content, $newTime, $event_id));
            $stmt->bind_param('sssd', $title, $content, $newTime, $event_id);
            $stmt->execute();
        }
        else {
            // insert an event
            $stmt = $sql->prepare("insert into events (user_id, title, content, wtime) values (?, ?, ?, ?)");
            if(!$stmt){
                phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
                exit;
            }
            $newTime = $day.' '.$time;

            phpLog($DEBUG, sprintf("dbSetEvent(), insert, bind_param:[%d][%s][%s][%s]", $_SESSION['user_id'], $title, $content, $newTime));

            $stmt->bind_param('dsss', $_SESSION['user_id'], $title, $content, $newTime);
            $stmt->execute();
        }

        $stmt->close();
    }

    phpLog($DEBUG, sprintf("dbSetEvent(), succ"));

    return true;
}


// delete a event
// input: comment's id
// return: if succ return true, or exit
function dbDelEvents($event_id) {
    global $DEBUG;

    $sql = connDb();

    if(!$event_id) {
        phpLog($DEBUG, sprintf("dbDelEvents, id param needed"));
        return false;
    }
    else {
        // delete comments which is child of the story
        $stmt = $sql->prepare("select user_id from events where event_id = ?");
        if(!$stmt){
            phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
            exit;
        }
        $stmt->bind_param('d', $event_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if($res->num_rows) {
            $row = $res->fetch_assoc();
            $result = $row;
        }
        
        phpLog($DEBUG, sprintf("dbDelEvents(), count:[%d]", $res->num_rows));
        phpLog($DEBUG, sprintf("dbDelEvents(), result:[%s]", print_r($result,true)));
        phpLog($DEBUG, sprintf("dbDelEvents(), session:[%d]", $_SESSION['user_id']));
    
        if($result['user_id'] != $_SESSION['user_id']) {
            return false;
        }

        // delete comments which is child of the story
        $stmt = $sql->prepare("delete from events where event_id = ?");
        if(!$stmt){
            phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
            exit;
        }
        $stmt->bind_param('d', $event_id);
        $stmt->execute();
    }

    $stmt->close();

    phpLog($DEBUG, sprintf("dbDelComment(), succ, id:[%d]",$event_id));

    return true;
}

// get events from database
// $from = '20221001' -> '2022-10-01';
// $to = '20221002' -> '2022-10-02';
// return: events array
function dbGetEvents($from, $to) {
    global $DEBUG;

    $from1 = substr($from, 0, 4).'-'.substr($from,4,2).'-'.substr($from,6,2);
    $to1 = substr($to, 0, 4).'-'.substr($to,4,2).'-'.substr($to,6,2);
    phpLog($DEBUG, sprintf("dbGetEvents(), from:[%s], to:[%s]", $from1, $to1));

    // for sql result
    $result = array();


	$sql = connDb();

    phpLog($DEBUG, sprintf("dbGetEvents(), id:[%s]", $_SESSION['user_id']));

    // event_id, user_id, title, content, wtime
	$query = "
		select a.event_id, a.user_id, concat(b.nickname,'(',b.username,')') username, a.title, a.content, a.wtime,
               substring(replace(a.wtime, '-', ''),1,8) yymmdd,
               substring(a.wtime, 12, 5) hhmi,
               substring(a.wtime, 1,4) yy, 
               substring(a.wtime, 6,2) mm, 
               substring(a.wtime, 9,2) dd, 
               substring(a.wtime, 12,2) hh, 
               substring(a.wtime, 15,2) mi 
		from events a, users b
		where a.user_id = b.user_id
        and a.user_id in (select ? union all select giver_id from sharing where taker_id = ?)
        and a.wtime between ? and timestampadd(day,1,?)
		order by a.wtime";
    phpLog($DEBUG, sprintf("dbGetEvents(), query:[%s]", $query));

	$stmt = $sql->prepare($query);
	if(!$stmt){
        phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
        exit;
	}

    // Bind the parameter and execute
    $stmt->bind_param('ddss', $_SESSION['user_id'], $_SESSION['user_id'], $from, $to);

	$stmt->execute();
	$res = $stmt->get_result();
	$stmt->close();

	// while ($res->num_rows && $row = $res->fetch_assoc()) {
    while ($row = $res->fetch_assoc()) {
        // Escaping output 
        $row['title'] = htmlentities($row['title']);
        $row['content'] = htmlentities($row['content']);

        $result[] = $row;
	}
    phpLog($DEBUG, sprintf("dbGetEvents(), count:[%d]", $res->num_rows));
    phpLog($DEBUG, sprintf("dbGetEvents(), result:[%s]", print_r($result,true)));
    
    return $result;
}


////////////////////////////
// calendar sharing
////////////////////////////
// get sharing info
// input: user_id
// return: sharing array
function dbGetSharing($user_id) {
    global $DEBUG;

    $result = array();
    $sql = connDb();

    // Use a prepared statement
    $query = "select a.sharing_id, a.taker_id, b.nickname, b.username from sharing a, users b where a.taker_id = b.user_id and a.giver_id = ?";
    $stmt = $sql->prepare($query);
	if(!$stmt){
        phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
        exit;
	}

    // Bind the parameter and execute
    $stmt->bind_param('d', $user_id);
    $stmt->execute();
	$res = $stmt->get_result();
	$stmt->close();


    while ($row = $res->fetch_assoc()) {
        $result[] = $row;
	}

    phpLog($DEBUG, sprintf("dbGetSharing(), count:[%d]", $res->num_rows));
    phpLog($DEBUG, sprintf("dbGetSharing(), result:[%s]", print_r($result,true)));
        
    return $result;
}

// get sharing info
// input: sharing_id
// return: if succ true else false
function dbDelSharing($sharing_id) {
    global $DEBUG;

    $sql = connDb();

    if(!$sharing_id) {
        phpLog($DEBUG, sprintf("dbDelSharing, sharing_id param needed"));
        return false;
    }
    else {
        // delete comments which is child of the story
        $stmt = $sql->prepare("select giver_id from sharing where sharing_id = ?");
        if(!$stmt){
            phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
            exit;
        }
        $stmt->bind_param('d', $sharing_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if($res->num_rows) {
            $row = $res->fetch_assoc();
            $result = $row;
        }
        
        // if request id and session id is diff, exit
        if($result['giver_id'] != $_SESSION['user_id']) {
            return false;
        }

        // delete comments which is child of the story
        $stmt = $sql->prepare("delete from sharing where sharing_id = ?");
        if(!$stmt){
            phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
            exit;
        }
        $stmt->bind_param('d', $sharing_id);
        $stmt->execute();
    }

    $stmt->close();

    phpLog($DEBUG, sprintf("dbDelSharing(), succ, id:[%d]",$sharing_id));

    return true;
}

// get available sharing users
// input: none
// return: available user array
function dbGetAvailableUser() {
    global $DEBUG;

    $result = array();
    $sql = connDb();

    // Use a prepared statement
    $query = "SELECT user_id, username, password, nickname, ctime FROM users where user_id not in (select ? union all select taker_id from sharing where giver_id = ?)";
    $stmt = $sql->prepare($query);
	if(!$stmt){
        phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
        exit;
	}

    // Bind the parameter and execute
    $stmt->bind_param('dd', $_SESSION['user_id'], $_SESSION['user_id']);
    $stmt->execute();
	$res = $stmt->get_result();
	$stmt->close();

	while ($res->num_rows && $row = $res->fetch_assoc()) {
        $result[] = $row;
    }

    phpLog($DEBUG, sprintf("dbGetAvailableUser(), count:[%d]", $res->num_rows));
    phpLog($DEBUG, sprintf("dbGetAvailableUser(), result:[%s]", print_r($result,true)));
        
    return $result;
}

// add sharing user_id to a logged user
// input: taker's user_id
// return: if succ true else false
function dbAddSharing($taker_id) {
    global $DEBUG;


    // before proceed, check session user_id
    if(!isset($_SESSION['user_id'])) {
        phpLog($DEBUG, sprintf("dbAddSharing(), login is needed"));
        return false;
    }
    else {
        $sql = connDb();

        $stmt = $sql->prepare("insert into sharing (giver_id, taker_id) values (?, ?)");
        if(!$stmt){
            phpLog($DEBUG, sprintf("Query Prep Failed: [%s]\n", $sql->error));
            exit;
        }

        phpLog($DEBUG, sprintf("dbAddSharing(), insert, bind_param:[%s][%s]", $_SESSION['user_id'], $taker_id));

        $stmt->bind_param('ss', $_SESSION['user_id'], $taker_id);
        $stmt->execute();

        $stmt->close();
    }

    phpLog($DEBUG, sprintf("dbAddSharing(), succ"));

    return true;
}
?>