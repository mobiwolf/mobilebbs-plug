<?php
require_once ('../../global.php');
header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="GET") {
	include_once (D_P. 'data/bbscache/olcache.php');
    $userinbbs = base64_encode($userinbbs);
    $guestinbbs = base64_encode($guestinbbs);
	echo "{\"list\":[{\"membernumber\":\"$userinbbs\",\"touristsnumber\":\"$guestinbbs\"}]}";
}else {
	$ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}


?>