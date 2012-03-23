<?php

defined('IN_DISCUZ') or exit;   
header("Content-type: application/json; charset=utf-8");

if($_SERVER['REQUEST_METHOD']=="POST") {
    $httpUid = trim(base64_decode($_SERVER['HTTP_UID']));
    global $_G;
    $ucsynlogout = $_G['setting']['allowsynlogin'] ? uc_user_synlogout() : '';
    if($_G['gp_formhash'] != $_G['formhash']) {
        //showmessage('logout_succeed', dreferer(), array('formhash' => FORMHASH, 'ucsynlogout' => $ucsynlogout));
    }
    //clearcookies();
    $_G['groupid'] = $_G['member']['groupid'] = 7;
    $_G['uid'] = $_G['member']['uid'] = 0;
    $_G['username'] = $_G['member']['username'] = $_G['member']['password'] = '';
    $_G['setting']['styleid'] = $_G['setting']['styleid'];
    $ecode = base64_encode("0");
    echo "{\"error\":\"$ecode\"}";
}else {
   $ecode = base64_encode("1");
   echo "{\"error\":\"$ecode\"}";
}

?>
