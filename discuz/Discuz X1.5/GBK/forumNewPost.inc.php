<?php

defined('IN_DISCUZ') or exit;
require 'tools.class.php';
header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="POST") {
    $httpUid = trim(base64_decode($_SERVER['HTTP_UID']));
    $httpFid = trim(base64_decode($_SERVER['HTTP_FID']));
    $httpPid = trim(base64_decode($_SERVER['HTTP_PID']));
    $httpTid = trim(base64_decode($_SERVER['HTTP_TID']));
    $httpAid = trim(base64_decode($_SERVER['HTTP_AID']));
    $httpMessage = mysql_real_escape_string(iconv("utf-8","gb2312",urldecode(base64_decode($_SERVER['HTTP_MESSAGE']))));
    $uid = !empty($httpUid)?$httpUid:0;
    $subject = "";
    $fid = !empty($httpFid)?$httpFid:0;
    $pid = !empty($httpPid)?$httpPid:0;
    $tid = !empty($httpTid)?$httpTid:0;
    if ($uid>0){
        $username = DB::result_first("SELECT username FROM ".DB::table('ucenter_members')." where uid='$uid'");
    }
    $author=$username;
    $authorId=$uid;
    $message = !empty($httpMessage)?$httpMessage:"";
    $readperm = 0;
    $price = 0;
    $typeid = 0;
    $sortid =0;
    $gtimestamp =$_G['timestamp'];
    $displayorder = 0;
    $digest = 0;
    $replycredit=0;
    $closed=0;
    $special=0;
    $moderated = 0;
    $status=32;
    $isgroup =0;
    if($uid==0){
        $ecode = base64_encode("5");
        echo "{\"error\":\"$ecode\"}";
        return;
    }elseif ($fid==0){
        $ecode = base64_encode("2");
        echo "{\"error\":\"$ecode\"}";
        return ;
    }elseif ($tid==0){
        $ecode = base64_encode("3");
        echo "{\"error\":\"$ecode\"}";
        return ;
    }elseif ($message==""&&$message==null) {
        $ecode = base64_encode("4");
        echo "{\"error\":\"$ecode\"}";
        return ;
    }elseif(empty($httpAid)&&strpos($message,'[attach]')==True){
       $ecode = base64_encode("5");
       echo "{\"error\":\"$ecode\"}";
       return ; 
    }
    $useip =$_G['clientip'];
    $pinvisible = 0;
    $isanonymous = 0;
    $usesig = 1;
    $htmlon = 0;
    $bbcodeoff = 0; 
    $smileyoff = 0; 
    $parseurloff = 0;
    $tagstr ="";
    $status=0;
    if (!empty($pid)){
        $bbcodeoff=0;
        $pquery = DB::query("SELECT * FROM ".DB::table('forum_post')." where pid='$pid'");
        if($pforum = DB::fetch($pquery)){
            $pauthor=$pforum['author'];
            $rep = "[b]»Ø¸´ [url=forum.php?mod=redirect&goto=findpost&pid=$pid&ptid=$tid][color=Olive]$pauthor [/color] µÄÌû×Ó[/url][/b] \n";
            $message = $rep.$message;
        }
    }
    $pquery = DB::result_first("SELECT  count(*) FROM ".DB::table('forum_post')." where pid=$pid and first=1");
    $message=urldecode($message);
    $attach = empty($httpAid)?"0":"2";
    $data= array(
        "fid" => $fid,
        "tid" => $tid,
        "first" => "0",
        "author" => $author,
        "authorid" => $authorId,
        "subject" => $subject,
        "dateline" => $gtimestamp,
        "message" => $message,
        "useip" => $useip,
        "invisible" => $pinvisible,
        "anonymous" => $isanonymous,
        "usesig" => $usesig,
        "htmlon" => $htmlon,
        "bbcodeoff" => $bbcodeoff,
        "smileyoff" => $smileyoff,
        "parseurloff" => $parseurloff,
        "attachment" => $attach,
        "tags" => $tagstr,
        "status" => $status
    );

    if(isset($tid)) {
        $tableid = DB::result_first("SELECT posttableid FROM ".DB::table('forum_thread')." WHERE tid='$tid'");
    } else {
        $tableid = $tid = 0;
    }
    $pid = DB::insert('forum_post_tableid', array('pid' => null), true);
    if(!$tableid) {
        $tablename = 'forum_post';
    } else {
        $tablename = "forum_post_$tableid";
    }
    DB::insert($tablename, $data);
    save_syscache('max_post_id', $pid);
    $operator = '+';
    $uidarray = $authorId;
    $action='post';
    $val = $operator == '+' ? 1 : -1;
    $extsql = array();
    if($action == 'reply') {
        $extsql = array('posts' => $val);
    } elseif($action == 'post') {
        $extsql = array('threads' => $val, 'posts' => $val);
    }
    $uidarray = (array)$uidarray;
    foreach($uidarray as $uid) {
        updatecreditbyaction($action, $uid, $extsql, '', $val, 1, $fid);
    }
    if($operator == '+' && ($action == 'reply' || $action == 'post')) {
        $uids = implode(',', $uidarray);
        DB::query("UPDATE ".DB::table('common_member_status')." SET lastpost='".TIMESTAMP."' WHERE uid IN ('$uids')", 'UNBUFFERED');
    }
    $subject = str_replace("\t", ' ', $subject);
    $lastpost = "$tid\t$subject\t$gtimestamp\t$author";
    DB::query("UPDATE ".DB::table('forum_forum')." SET lastpost='$lastpost', posts=posts+1, todayposts=todayposts+1 WHERE fid='$fid'", 'UNBUFFERED');
    DB::query("UPDATE ".DB::table('forum_thread')." SET lastpost='$gtimestamp', views=views+1, replies=replies+1 WHERE tid='$tid'", 'UNBUFFERED');
    if (!empty($httpAid))
    {
       DB::update('forum_attachment',array('tid' => $tid,'pid'=>$pid),"aid IN (".$httpAid.")");
    }
    $ecode = base64_encode("0");
    echo "{\"error\":\"$ecode\"}";
}else {
    $ecode = base64_encode("0");
    echo "{\"error\":\"$ecode\"}";
}

?>
