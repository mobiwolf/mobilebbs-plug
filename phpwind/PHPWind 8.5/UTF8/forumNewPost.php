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
    $httpPid = trim(base64_decode($_SERVER['HTTP_PID']));
    $httpTid = trim(base64_decode($_SERVER['HTTP_TID']));
    $httpAid = trim(base64_decode($_SERVER['HTTP_AID']));
    $httpMessage = addslashes(trim(base64_decode($_SERVER['HTTP_MESSAGE'])));
    $httpLou = trim(base64_decode($_SERVER['HTTP_LOU']));

    //$httpUid = 2;
    //$httpFid = 11;
    //$httpPid = 79;
    //$httpTid = 55;
    //$httpAid = '';
    //$httpMessage = iconv("gb2312","utf-8",'这是一个演示程序。。。。');
    //$httpLou = 2;
    
	$uid = empty($httpUid)?0:intval($httpUid);
	$fid = empty($httpFid)?0:intval($httpFid);
	$tid = empty($httpTid)?0:intval($httpTid);
	$pid = empty($httpPid)?0:intval($httpPid);
    
    $tabPre = $db->dbpre;
	$lou = empty($httpLou)?0:intval($httpLou);
	if ($uid>0){
        $username = $db->get_value("SELECT username FROM ".$tabPre."members where uid='$uid'  LIMIT 1");
	}
	$author=$username;
	$authorid=$uid;
	$postdate=time();
	$userip=$userip;//IP
	$ifsign=1;
	$ipfrom="手机用户";
	$ifconvert = empty($httpAid)?1:2;
	$ifwordsfb=1;
	$ifcheck=1;
	$subject="";
	$message = urldecode($httpMessage);
	
	if (intval($uid)==0){
		$ecode = base64_encode("5");
        echo "{\"error\":\"$ecode\"}";
        return;
	}elseif (intval($fid)==0){
		$ecode = base64_encode("2");
        echo "{\"error\":\"$ecode\"}";
		return ;
	}elseif (intval($tid)==0) {
		$ecode = base64_encode("3");
        echo "{\"error\":\"$ecode\"}";
		return ;
	}elseif (empty($message)) {
		$ecode = base64_encode("4");
        echo "{\"error\":\"$ecode\"}";
		return ;
	}
	//回帖
	if (intval($pid)!=0){
        $pauthor = iconv("utf-8","gb2312",$db->get_value("SELECT author FROM ".$tabPre."posts where pid='$pid'  LIMIT 1"));
		$name = "回 ".$lou."楼(".$pauthor.") 的帖子";
        $subject = iconv("gb2312","utf-8", $name);
	}
    
    $aid = empty($httpAid)?0:1;
	$apid = pwQuery::insert($tabPre.'posts', array(
                    'fid'       => $fid,
                    'tid'       => $tid,
                    'aid'       => $aid,
                    'author'    => $author,
                    'authorid'  => $authorid,
                    'postdate'  => $postdate,
                    'subject'   => $subject,
                    'userip'    => $userip,
                    'ifsign'    => $ifsign,
                    'ipfrom'    => $ipfrom,
                    'ifconvert' => $ifconvert,
                    'ifwordsfb' => $ifwordsfb,
                    'ifcheck'   => $ifcheck,
                    'content'   => $message
                ));
	//修改帖子数据
	$forumquery=$db->query("SELECT * FROM ".$tabPre."threads where tid=$tid");
		if ($quforum=$db->fetch_array($forumquery)){
			$uphits=$quforum['hits']+1;
			$upreplies=$quforum['replies']+1;
            pwQuery::update($tabPre.'threads', 'tid=:tid', array($tid), 
                             array('lastpost'   => $postdate,
                                   'lastposter' => $author,
                                   'hits'       => $uphits,
                                   'replies'    => $upreplies));
			//修改板块
			$quforum=$db->get_one("SELECT fid,article,tpost FROM ".$tabPre."forumdata where fid='$fid' limit 1 ");
			if (intval($quforum)>0){
				$upfid=$quforum['fid'];
                $uptopic=$quforum['topic']+1;
				$uparticle=$quforum['article']+1;
				$uptpost=$quforum['tpost']+1;
				$uplastport=$subject."	".$author."	".time()."	"."read.php?tid=$tid#page=e#a";
                pwQuery::update($tabPre.'forumdata', 'fid=:upfid', array($upfid),
                                array('tpost'       => $uptpost,
                                      'topic'       => $uptopic,
                                      'article'     => $uparticle,
                                      'lastpost'    => $uplastport));
				if (!empty($httpAid))
                {
                    pwQuery::update($tabPre.'attachs','aid in (:httpAid)',array($httpAid),
                                    array('uid' => $uid,
                                          'tid' => $tid,
                                          'pid' => $apid,
                                          'ifthumb' =>2
                                    ));
                    
                }
                $ecode = base64_encode("0");
                echo "{\"error\":\"$ecode\"}";
			}else {
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