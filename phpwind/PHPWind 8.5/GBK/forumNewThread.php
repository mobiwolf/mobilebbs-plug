<?php

require_once ('../../global.php');
header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="POST") {
    if($_SERVER['HTTP_X_FORWARDED_FOR']){
	    $userip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    $c_agentip=1;
		} elseif($_SERVER['HTTP_CLIENT_IP']){
		    $userip = $_SERVER['HTTP_CLIENT_IP'];
		    $c_agentip=1;
		} else{
		    $userip = $_SERVER['REMOTE_ADDR'];
		    $c_agentip=0;
		}
	
    $httpUid = trim(base64_decode($_SERVER['HTTP_UID']));
    $httpFid = trim(base64_decode($_SERVER['HTTP_FID']));
    $httpSubject = mysql_real_escape_string(iconv("utf-8","gb2312",base64_decode($_SERVER['HTTP_SUBJECT'])));
    $httpMessage = mysql_real_escape_string(iconv("utf-8","gb2312",base64_decode($_SERVER['HTTP_MESSAGE'])));
    $httpAid = trim(base64_decode($_SERVER['HTTP_AID']));
    
	
	$uid = empty($httpUid)?0:intval($httpUid);
    $fid = empty($httpFid)?0:intval($httpFid);
    $tabPre = $db->dbpre;
   if ($uid>0){
		$username = $db->get_value("SELECT username FROM ".$tabPre."members where uid='$uid'  LIMIT 1");
	}
	$author = $username;
	$authorid = $uid;
	$subject = urldecode($httpSubject);
	$message = urldecode($httpMessage);
	$lastposter=$author;//最后回复人name
	$userip=$userip;//IP
	$ifconvert = empty($httpAid)?1:2;
    $ifupload = empty($httpAid)?0:1;
    $aid = $ifupload;

	if ($fid==0){
		$ecode = base64_encode("2");
        echo "{\"error\":\"$ecode\"}";
        return;
	}elseif (empty($subject)) {
		$ecode = base64_encode("3");
        echo "{\"error\":\"$ecode\"}";
        return;
	}elseif (empty($message)) {
		$ecode = base64_encode("4");
        echo "{\"error\":\"$ecode\"}";
        return;
	}

	//执行添加操作分别对pw_threads、pw_tmsgs
    $tid = pwQuery::insert($tabPre.'threads', array('fid'        => $fid,
                                               'author'     => $author,
                                               'authorid'   => $authorid,
                                               'subject'    => $subject,
                                               'ifcheck'    => 1,
                                               'postdate'   => time(),
                                               'lastpost'   => time(),
                                               'lastposter' => $lastposter,
                                               'hits'       => 1,
                                               'replies'    => 0,
                                               'ifupload'   => $ifupload));
	if ($tid>0){
        pwQuery::insert($tabPre.'tmsgs', array('tid'         => $tid,
                                          'aid'         => $aid,
                                          'userip'      => $userip,
                                          'ifsign'      => 1,
                                          'ipfrom'      => iconv("utf-8","gb2312","手机地址"),
                                          'ifconvert'   => $ifconvert,
                                          'ifwordsfb'   => 1,
                                          'content'     => $message));     
        
		$forumquery=$db->query("SELECT * FROM ".$tabPre."forumdata where fid=$fid");
		if ($quforum=$db->fetch_array($forumquery)){
			$upfid=$quforum['fid'];
			$uptopic=$quforum['topic']+1;
			$uparticle=$quforum['article']+1;
			$uptpost=$quforum['tpost']+1;
			$uplastport=$subject."	".$author."	".time()."	"."read.php?tid=$tid#page=e#a";
			//执行修改操作
			pwQuery::update($tabPre.'forumdata', 'fid=:upfid', array($upfid),
                                array('tpost'       => $uptpost,
                                      'topic'       => $uptopic,
                                      'article'     => $uparticle,
                                      'lastpost'    => $uplastport));
            if (!empty($httpAid)){
                    pwQuery::update($tabPre.'attachs','aid in (:httpAid)',array($httpAid),
                                    array('uid' => $uid,
                                          'tid' => $tid,
                                          'pid' => 0,
                                          'ifthumb' =>2
                                    ));
                    
                }
            $ecode = base64_encode("0");
            echo "{\"error\":\"$ecode\"}";
		}else{
			$ecode = base64_encode("1");
            echo "{\"error\":\"$ecode\"}";
		}
	}else {
		$ecode = base64_encode("1");
        echo "{\"error\":\"$ecode\"}";
	}
}else {
	$ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}

?>