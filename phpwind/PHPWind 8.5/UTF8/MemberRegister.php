<?php

define('SCR','register');
require_once ('../../global.php');
header("Content-type: application/json; charset=utf-8"); 
if($_SERVER['REQUEST_METHOD']=="POST") {
	$hname = trim(urldecode(base64_decode($_SERVER['HTTP_USERNAME'])));
    $hpwd = trim(urldecode(base64_decode($_SERVER['HTTP_PASSWORD'])));
    $hmail = trim(base64_decode($_SERVER['HTTP_EMAIL']));
    //$hname = trim(urldecode(base64_decode('5bKC5Y+v5YiY')));
    //$hpwd = trim(urldecode(base64_decode('MDIxNTgx')));
    //$hmail = trim(base64_decode('d2VibHNxQHBwcy5jb20='));
	if (empty($hname)){
		$ecode = base64_encode("2");
        echo "{\"error\":\"$ecode\"}";
		return ;
	}elseif (empty($hpwd)) {
		$ecode = base64_encode("3");
        echo "{\"error\":\"$ecode\"}";
		return ;
	}elseif (empty($hmail)) {
		$ecode = base64_encode("4");
        echo "{\"error\":\"$ecode\"}";
		return ;
	}elseif(!(ereg("^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+",$hmail))){
		$ecode = base64_encode("5");
        echo "{\"error\":\"$ecode\"}";return ;
	}
    
    require_once(R_P . 'uc_client/uc_client.php');
    if (uc_user_get($hname)) {
        $ecode = base64_encode("6");
        echo "{\"error\":\"$ecode\"}";
        return ;//用户名已经存在
    }
    if (uc_user_get($hmail,2)) {
        $ecode = base64_encode("7");
        echo "{\"error\":\"$ecode\"}";
        return ;//邮箱已经存在
    }
    $sRegpwd = $regpwd;
    $register = L::loadClass('Register', 'user');
    $rg_config['rg_allowregister']==2 && $register->checkInv($invcode);
    $register->checkSameNP($hname, $hpwd);
    $customfield = L::config('customfield','customfield');
    $register->setStatus(11);
    $hmailtoall && $register->setStatus(7);
    $register->setName($hname);
    $register->setPwd($hpwd, $hpwd);
    $register->setEmail($hmail);
    $register->setSafecv($question, $customquest, $answer);
    $register->setReason($regreason);
    //$register->setCustomfield(L::config('customfield','customfield'));
    $register->setCustomdata($customdata);
    $register->execute();

    if ($rg_config['rg_allowregister']==2) {
        $register->disposeInv();
    }
    list($winduid, $rgyz, $safecv) = $register->getRegUser();

    $windid  = $hname;
    $windpwd = md5($hpwd);
    if ($rg_config['rg_allowsameip']) {
        if (file_exists(D_P.'data/bbscache/ip_cache.php')) {
            pwCache::setData(D_P.'data/bbscache/ip_cache.php',"<$onlineip>", false, "ab");
        } else {
            pwCache::setData(D_P.'data/bbscache/ip_cache.php',"<?php die;?><$timestamp>\n<$onlineip>");
        }
    }
    //addonlinefile();
    if (GetCookie('userads') && $inv_linkopen && $inv_linktype == '1') {
        require_once(R_P.'require/userads.php');
    }
    if (GetCookie('o_invite') && $db_modes['o']['ifopen'] == 1) {
        list($o_u,$hash,$app) = explode("\t",GetCookie('o_invite'));
        if (is_numeric($o_u) && strlen($hash) == 18) {
            require_once(R_P.'require/o_invite.php');
        }
    }
    if ($rgyz == 1) {
        Cookie("winduser",StrCode($winduid."\t".PwdCode($windpwd)."\t".$safecv));
        Cookie("ck_info",$db_ckpath."\t".$db_ckdomain);
        Cookie('lastvisit','',0);
    }
    //发送短消息
    if ($rg_config['rg_regsendmsg']) {
        $rg_config['rg_welcomemsg'] = str_replace('$rg_name', $hname, $rg_config['rg_welcomemsg']);
        M::sendNotice(
            array($windid),
            array(
                'title' => "Welcome To[{$db_bbsname}]!",
                'content' => $rg_config['rg_welcomemsg'],
            )
        );
    }
    //发送邮件
    @include_once pwCache::getPath(D_P.'data/bbscache/mail_config.php');
    if ($rg_config['rg_emailcheck']) {
        $verifyhash = GetVerify();
        $rgyz = md5($rgyz . substr(md5($db_sitehash),0,5) . substr(md5($hname),0,5));
        require_once(R_P.'require/sendemail.php');
        $sendinfo = sendemail($hmail, 'email_check_subject', 'email_check_content', 'email_additional');
        if ($sendinfo === true) {
            ObHeader("$db_registerfile?step=finish&email=$hmail&verify=$verifyhash");
        } else {
            Showmsg(is_string($sendinfo) ? $sendinfo : 'reg_email_fail');
        }
    } elseif ($rg_config['rg_regsendemail'] && $ml_mailifopen) {
        require_once(R_P.'require/sendemail.php');
        sendemail($hmail,'email_welcome_subject','email_welcome_content','email_additional');
    }
    //发送结束
    //passport
    if ($db_pptifopen && $db_ppttype == 'server' && ($db_ppturls || $forward)) {
        $action = 'login';
        $jumpurl = $forward ? $forward : $db_ppturls;
        empty($forward) && $forward = $db_bbsurl;
        require_once(R_P.'require/passport_server.php');
    }
    //passport
    $verifyhash = base64_encode($winduid);
    $ecode = base64_encode("0");
    echo "{\"error\":\"$ecode\",\"uid\":\"$verifyhash\"}";   
    
}else {
	$ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}

?>