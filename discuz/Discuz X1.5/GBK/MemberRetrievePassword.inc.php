<?php

header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="POST") {
    $username = trim(iconv("utf-8","gb2312",urldecode(base64_decode($_SERVER['HTTP_USERNAME']))));
    $email= trim(base64_decode($_SERVER['HTTP_EMAIL']));
    $query = DB::result(DB::query("SELECT count(1) FROM ".DB::table('ucenter_members')." where username='$username' and email='$email'"));
    $ecode = base64_encode("2");
    if($query){
        $ecode = base64_encode("0");
    }
    echo "{\"error\":\"$ecode\"}";
}else {
    $ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}

?>