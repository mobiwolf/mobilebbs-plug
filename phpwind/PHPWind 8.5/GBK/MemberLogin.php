<?php

require_once ('../../global.php');
require_once ('tool.php');
header("Content-type:application/json;charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="POST") {
    $pwuser = trim(iconv("utf-8","gb2312",urldecode(base64_decode($_SERVER['HTTP_USERNAME']))));
    $pwpwd = trim(iconv("utf-8","gb2312",urldecode(base64_decode($_SERVER['HTTP_PASSWORD']))));
    
    $pwuser = empty($pwuser)?"":urldecode($pwuser);
    $pwpwd = empty($pwpwd)?"":urldecode($pwpwd);
    if (empty($pwuser)){
        $code = base64_encode("3");
        echo "{\"error\":\"$code\"}";
        return ;
    }elseif (empty($pwpwd)){
        $code = base64_encode("4");
        echo "{\"error\":\"$code\"}";
        return ;
    }
    $md5_pwpwd = md5($pwpwd);
    $safecv = $db_ifsafecv ? questcode($question, $customquest, $answer) : '';

    //PostCheck(0,$db_gdcheck & 2,$db_ckquestion & 2 && $db_question,0);
    require_once(R_P . 'require/checkpass.php');
    $logininfo = checkpass($pwuser, $md5_pwpwd, $safecv, $lgt);
    if (!is_array($logininfo)) {
        $code = base64_encode("2");
        echo "{\"error\":\"$code\"}";
        return;
    }
    list($winduid, $groupid, $windpwd, $showmsginfo) = $logininfo;
    perf::gatherInfo('changeMembersWithUserIds', array('uid'=>$winduid));
    if (file_exists(D_P."data/groupdb/group_$groupid.php")) {
        require_once pwCache::getPath(S::escapePath(D_P."data/groupdb/group_$groupid.php"));
    } else {
        require_once pwCache::getPath(D_P."data/groupdb/group_1.php");
    }
    (int)$keepyear && $cktime = '31536000';
    $cktime != 0 && $cktime += $timestamp;
    Cookie("winduser",StrCode($winduid."\t".$windpwd."\t".$safecv),$cktime);
    Cookie("ck_info",$db_ckpath."\t".$db_ckdomain);
    Cookie('lastvisit','',0);
    if ($db_autoban) {
        require_once(R_P.'require/autoban.php');
        autoban($winduid);
    }
    ($_G['allowhide'] && $hideid) ? Cookie('hideid',"1",$cktime) : Loginipwrite($winduid);
    (empty($jumpurl) || false !== strpos($jumpurl, $regurl)) && $jumpurl = $db_bfn;

    if (GetCookie('o_invite') && $db_modes['o']['ifopen'] == 1) {
        list($o_u,$hash,$app) = explode("\t",GetCookie('o_invite'));
        if (is_numeric($o_u) && strlen($hash) == 18) {
            require_once(R_P.'require/o_invite.php');
            }
    }
    //passport
    if ($db_pptifopen && $db_ppttype == 'server' && ($db_ppturls || $forward)) {
        $tmp = $jumpurl;
        $jumpurl = $forward ? $forward : $db_ppturls;
        $forward = $tmp;
        require_once(R_P.'require/passport_server.php');
    }
    $ecode = base64_encode(0);
	$ary = getstatebyuid($winduid);
    $state = $ary['state'];
    $sdate = $ary['sdate'];
    $edate = $ary['edate'];
    $euid = base64_encode($winduid);

    echo "{\"error\":\"$ecode\",\"uid\":\"$euid\",\"userstate\":\"$state\",\"sdate\":\"$sdate\",\"edate\":\"$edate\"}";
}else {
    $ecode = base64_encode(1);
    echo "{\"error\":\"$ecode\"}";
}

?>
