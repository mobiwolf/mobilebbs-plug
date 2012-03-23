<?php

require 'json.class.php';
require 'tools.class.php';
require 'forumcommon.php';
require_once libfile('function/forum');
header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="GET") {
    $httpFid = trim(base64_decode($_SERVER['HTTP_FID']));
    $httpPage = trim(base64_decode($_SERVER['HTTP_PAGE']));
    $httpSize = trim(base64_decode($_SERVER['HTTP_PAGESIZE']));
    $huid = trim(base64_decode($_SERVER['HTTP_UID']));
    $Fid = intval($httpFid)<=0?0:intval($httpFid);
    $Page = intval($httpPage)>0?intval($httpPage):0;
    $Size = intval($httpSize)>0?intval($httpSize):10;

    if ($Fid<=0){
       $ecode = base64_encode("2");
       echo "{\"error\":\"$ecode\"}";;
       return ;
    }
    $frontnumber = ($Page*$Size);
    $sql = "SELECT * ,replies FROM ".DB::table('forum_thread')." where fid='$Fid' and displayorder>='0' order by lastpost desc LIMIT $frontnumber, $Size";
    $query = DB::query($sql);
    $zjson['list']=array();
    while($sub = DB::fetch($query)) {
        $tid = $sub['tid'];
        $PFid = $sub['fid'];
        $author = iconv("gb2312","utf-8",$sub['author']);
        $authorId = $sub['authorid'];
        $subject = iconv("gb2312","utf-8",trim($sub['subject']));
        $views = $sub['views'];
        $replies = $sub['replies'];
        $dateline = dgmdate($sub['dateline'],'d');
        $lastpost = tool::dgmtimedate($sub['lastpost'],'u');
        $lastposter = iconv("gb2312","utf-8",$sub['lastposter']);
        $mimgurl = discuz_uc_avatar($authorId,'middle',TRUE);
        $authorId = $authorId;
        $closed = $sub['closed'];
        $Uid = $_G['uid'];
        $threads = DB::result_first("SELECT  count(*) FROM ".DB::table('forum_thread')." where fid='$Fid' ");
        $UserState = base64_encode(intval($huid)==0?0:get_user_state_by_id($huid));
        $platearray = array("tid"           => base64_encode($tid),
                            "fid"           => base64_encode($PFid),
                            "author"        => base64_encode($author),
                            "authorId"      => base64_encode($authorId),
                            "subject"       => base64_encode($subject),
                            "views"         => base64_encode($views),
                            "replies"       => base64_encode($replies),
                            "threads"       => base64_encode($threads),
                            "dateline"      => base64_encode($dateline),
                            "lastpost"      => base64_encode($lastpost),
                            "lastposter"    => base64_encode($lastposter),
                            "mimgurl"       => base64_encode($mimgurl),
                            "closed"        => base64_encode($closed),
                            "userstate"     => base64_encode($UserState)
                            );
        array_push($zjson['list'],$platearray);
    }
    echo ArrayJSON($zjson);
}else {
    $ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}
?>
