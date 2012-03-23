<?php

defined('IN_DISCUZ') or exit;
require 'forumcommon.php';
header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="POST") {
	$httpusername = trim(iconv("utf-8","gb2312",urldecode(base64_decode($_SERVER['HTTP_USERNAME']))));
    $httpUpassword = trim(base64_decode($_SERVER['HTTP_PASSWORD']));
    $Uusername=empty($httpusername)?"":urldecode($httpusername);
    $Upassword=empty($httpUpassword)?"":urldecode($httpUpassword);
    if (empty($Uusername)){
        $code = base64_encode("4");
        echo "{\"error\":\"$code\"}";
        return ;
    }elseif (empty($Upassword)){
        $code = base64_encode("5");
        echo "{\"error\":\"$code\"}";
        return ;
    }
    $query = DB::query("SELECT uid, username, password,salt FROM ".DB::table('ucenter_members')." where username='$Uusername'");
    if($forum = DB::fetch($query)){
        $stlt=$forum['salt'];
        $mpwd=md5(md5($Upassword).$stlt);
        if ($mpwd==$forum['password']){
            
        $uid=$forum['uid'];
        header('P3P: CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
        $query1 = DB::query("SELECT uid, username, password FROM ".DB::table('common_member')." WHERE uid='$uid'");
            if($member = DB::fetch($query1)) {
                dsetcookie('auth', authcode("$member[password]\t$member[uid]", 'ENCODE'), $cookietime);
                $code = base64_encode("0");
				$state = base64_encode(get_user_state_by_id($uid));
                $uid = base64_encode("$uid");
                echo "{\"error\":\"$code\",\"uid\":\"$uid\",\"userstate\":\"$state\"}";
            }else{
                $code = base64_encode("1");
                echo "{\"error\":\"$code\"}";
                }
        }else {
            $code = base64_encode("2");
            echo "{\"error\":\"$code\"}";
        }
    }else{
        $code = base64_encode("3");
        echo "{\"error\":\"$code\"}";
    }
}else {
    $code = base64_encode("1");
    echo "{\"error\":\"$code\"}";
}

?>