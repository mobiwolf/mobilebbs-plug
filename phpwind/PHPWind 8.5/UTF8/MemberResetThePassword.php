<?php
require_once ('../../global.php');
require_once ('json.class.php');
header("Content-type: application/json; charset=UTF-8 "); 

if($_SERVER['REQUEST_METHOD']=="POST") {
	$username = trim(urldecode(base64_decode($_SERVER['HTTP_USERNAME'])));
    $email = trim(base64_decode($_SERVER['HTTP_EMAIL']));
    $password = trim(base64_decode($_SERVER['HTTP_PASSWORD']));
    $password2 = trim(base64_decode($_SERVER['HTTP_PASSWORD2']));
	
	if ($password!=$password2){
		$ecode = base64_encode("2");
        echo "{\"error\":\"$ecode\"}";
	}
	$pwd=md5($password);
	$tabPre = $db->dbpre;
	$quid = $db->get_value("SELECT uid FROM ".$tabPre."members where username = '$username' and email = '$email'");
	if (intval($quid)>0){
		$db->update("UPDATE ".$tabPre."members SET `password`='$pwd' WHERE `uid`='$quid';");
		$ecode = base64_encode("0");
        echo "{\"error\":\"$ecode\"}";
	}else{
		$ecode = base64_encode("2");
        echo "{\"error\":\"$ecode\"}";
	}
}else {
	$ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}
?>