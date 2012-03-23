<?php

require 'json.class.php';
require 'tools.class.php';
require 'forumcommon.php';
require_once libfile('function/forum');
header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="GET") {
    $httpSize = trim(base64_decode($_SERVER['HTTP_PAGESIZE']));
	$huid = trim(base64_decode($_SERVER['HTTP_UID']));
	$Model = intval(trim(base64_decode($_SERVER['HTTP_MODEL'])));
    $Size = intval($httpSize)>0?intval($httpSize):10;
    $StartPos = 0;
    $searchid = DB::result_first("SELECT searchid FROM ".DB::table('common_searchindex')." order by searchid desc LIMIT 0,1");
    $searchid = intval($searchid)>0?$searchid:0;
    $sql = "SELECT searchstring, keywords, num, ids FROM ".DB::table('common_searchindex')." WHERE searchid='$searchid' AND srchmod='2'";
    $searchquery = DB::query($sql);
    if($search = DB::fetch($searchquery)) {
        $sthreadquery=DB::query("SELECT * FROM ".DB::table('forum_thread')." WHERE tid IN ($search[ids]) AND displayorder>='0' ORDER BY lastpost desc LIMIT $StartPos, $Size");
        $zjson['list']=array();
        while ($sthread = DB::fetch($sthreadquery)){
            $tid = $sthread['tid'];
            $Fid = $sthread['fid'];
            $author = $sthread['author'];
            $authorId = $sthread['authorid'];
            $sthreadject = $sthread['subject'];
            $views = $sthread['views'];
            $replies = $sthread['replies'];
            $dateline = urlencode(dgmdate($sthread['dateline'],'d'));
            $lastpost = urlencode(tool::dgmtimedate($sthread['lastpost'],'u'));
            $lastposter = $sthread['lastposter'];
            $mimgurl = discuz_uc_avatar($authorId,'middle',TRUE);
            $closed = $sthread['closed'];
            $posttable = $heat['posttableid'] ? "forum_post_{$heat['posttableid']}" : 'forum_post';
            $sql = "SELECT message FROM ".DB::table($posttable)." where tid ='$tid' LIMIT 0,1";
            $message = DB::result_first($sql);
            $tid = $tid;
            $message = rtrim($message);
            if ($Model==1){
               $msglist = tool::discuzcode($message, 0,True,True); 
            }else{
               $msglist = tool::discuzcode($message, 0,False,False);  
            }
            $msglist = tool::deleMessage($message);
            $message = $msglist['message'];
    
            $UserState = base64_encode(intval($huid)==0?0:get_user_state_by_id($huid));
            $platearray = array("tid"           => base64_encode($tid),
                                "fid"           => base64_encode($Fid),
                                "author"        => base64_encode(iconv("gb2312","utf-8",$author)),
                                "authorId"      => base64_encode($authorId),
                                "subject"       => base64_encode(iconv("gb2312","utf-8",$sthreadject)),
                                "message"       => base64_encode(iconv("gb2312","utf-8",$message)),
                                "views"         => base64_encode($views),
                                "replies"       => base64_encode($replies),
                                "dateline"      => base64_encode(iconv("gb2312","utf-8",$dateline)),
                                "lastpost"      => base64_encode($lastpost),
                                "lastposter"    => base64_encode(iconv("gb2312","utf-8",$lastposter)),
                                "mimgurl"       => base64_encode($mimgurl),
                                "closed"        => base64_encode($closed),
                                "userstate"     => base64_encode($UserState)
                                );
            array_push($zjson['list'],$platearray);
        }
        echo ArrayJSON($zjson);
    }else{
        echo "{\"list\":[]}";
    }
}else{
    $ecode = base64_encode("1");
    echo "{\"error\":$ecode}";
}
?>