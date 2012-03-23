<?php

defined('IN_DISCUZ') or exit;
header("Content-type: application/json; charset=utf-8"); 
if($_SERVER['REQUEST_METHOD']=="POST") {
    $username = urldecode(trim(base64_decode($_SERVER['HTTP_USERNAME'])));
    $email = trim(base64_decode($_SERVER['HTTP_EMAIL']));
    $password = trim(base64_decode($_SERVER['HTTP_PASSWORD']));
    $password2 = trim(base64_decode($_SERVER['HTTP_PASSWORD2']));
    
    if (empty($password)||empty($password2)){
       $ecode = base64_encode("2");
       echo "{\"error\":\"$ecode\"}";
    }elseif ($password!=$password2){
       $ecode = base64_encode("3");
       echo "{\"error\":\"$ecode\"}";
    }else{
        require_once libfile('function/core');
        loaducenter();
        $questionid = $_G['gp_clearquestion'] ? 0 : '';
        $member['username'] = $username;
        $_G['gp_passwordnew'] = $password;
        $_G['gp_emailnew'] = $email;
        $ucresult = uc_user_edit($member['username'], '', $_G['gp_passwordnew'], $_G['gp_emailnew'], 1);
        if($ucresult == -4) {
            $ecode = base64_encode("4");
            echo "{\"error\":\"$ecode\"}";
            // Email 格式有误
        } elseif($ucresult == -6) {
            $ecode = base64_encode("5");
            echo "{\"error\":\"$ecode\"}";//该 Email 已经被注册
        } elseif($ucresult == -8) {
            $ecode = base64_encode("6");
            echo "{\"error\":\"$ecode\"}"; //该用户受保护无权限更改
        } elseif($ucresult == 1) {
            if(!empty($_G['gp_newpassword']) || $secquesnew) {
                $setarr['password'] = md5(random(10));
                }
            if($_G['setting']['connect']['allow']) {
                DB::update('common_member_connect', array('conisregister' => 0), array('uid' => $_G['uid']));
            }

            $authstr = false;
            if($emailnew != $_G['member']['email']) {
                $authstr = true;
                emailcheck_send($space['uid'], $emailnew);
                dsetcookie('newemail', "$space[uid]\t$emailnew\t$_G[timestamp]", 31536000);
            }
            if($setarr) {
                DB::update('common_member', $setarr, array('uid' => $_G['uid']));
            }

            if($authstr) {
               $ecode = base64_encode("7");
               echo "{\"error\":\"$ecode\"}"; //邮箱
            } else {
               $ecode = base64_encode("0");
               echo "{\"error\":\"$ecode\"}"; 
            }
        }else if ($ucresult==0){
            $ecode = base64_encode("-1");
            echo "{\"error\":\"$ecode\"}";
            //与老邮箱不一致； 
        }  
    }
}else {
    $ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}

?>