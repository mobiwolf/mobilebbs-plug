<?php
require_once ('../../global.php');
require_once ('json.class.php');
header("Content-type: application/json; charset=UTF-8 "); 

if($_SERVER['REQUEST_METHOD']=="POST") {
	$username = trim(iconv("utf-8","gb2312",urldecode(base64_decode($_SERVER['HTTP_USERNAME']))));
    $email= trim(iconv("utf-8","gb2312",base64_decode($_SERVER['HTTP_EMAIL'])));
	$tabPre = $db->dbpre;
    $qvalue = $db->get_value("SELECT count(1) FROM ".$tabPre."members where username = '$username' and email = '$email'");
	$ecode = base64_encode("2");
    if (intval($qvalue)==1){
        $ecode = base64_encode("0");
	}
    echo "{\"error\":\"$ecode\"}";
}else {
	$ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}


?>