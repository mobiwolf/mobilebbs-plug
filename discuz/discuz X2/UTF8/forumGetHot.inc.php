<?php

require 'json.class.php';
require_once libfile('function/forum');
require 'tools.class.php';
require 'forumcommon.php';
header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="GET") { 
    $httpSize = trim(base64_decode($_SERVER['HTTP_PAGESIZE']));
    $huid = trim(base64_decode($_SERVER['HTTP_UID']));
    $Size = intval($httpSize)>0?intval($httpSize):10;
    $StartPos = 0;
	global $_G;
	$addsql = '';
	$data = array();
	if($_G['setting']['indexhot']['status']) {
		require_once libfile('function/post');
		$_G['setting']['indexhot'] = array(
			'status' => 1,
			'limit' => intval($_G['setting']['indexhot']['limit'] ? $_G['setting']['indexhot']['limit'] : 10),
			'days' => intval($_G['setting']['indexhot']['days'] ? $_G['setting']['indexhot']['days'] : 7),
			'expiration' => intval($_G['setting']['indexhot']['expiration'] ? $_G['setting']['indexhot']['expiration'] : 900),
			'messagecut' => intval($_G['setting']['indexhot']['messagecut'] ? $_G['setting']['indexhot']['messagecut'] : 200)
		);
		$heatdateline = TIMESTAMP - 86400 * $_G['setting']['indexhot']['days'];
		if(!$_G['setting']['groupstatus']) {
			$addtablesql = " LEFT JOIN ".DB::table('forum_forum')." f ON f.fid = t.fid ";
			$addsql = " AND f.status IN ('0', '1') ";
		}
		$query = DB::query("SELECT t.tid, t.fid,t.posttableid,t.views,t.dateline,t.lastpost,t.lastposter,t.closed,t.replies,t.author,t.authorid,t.subject,t.price
			FROM ".DB::table('forum_thread')." t $addtablesql
			WHERE t.dateline>'$heatdateline' AND t.heats>'0' AND t.displayorder>='0' $addsql ORDER BY t.heats DESC LIMIT $StartPos, $Size");
		$messageitems = 2;
		$limit = $_G['setting']['indexhot']['limit'];
			$zjson['list']=array();
		while($heat = DB::fetch($query)) {
			$tid = $heat['tid'];
            $Fid = $heat['fid'];
            $author = $heat['author'];
            $authorId = $heat['authorid'];
            $sthreadject = $heat['subject'];
            $views = $heat['views'];
            $replies = $heat['replies'];
            $dateline = urlencode(dgmdate($heat['dateline'],'d'));
            $lastpost = urlencode(tool::dgmtimedate($heat['lastpost'],'u'));
            $lastposter = $heat['lastposter'];
            $mimgurl = urldecode(discuz_uc_avatar($authorId,'middle',TRUE));
			$authorId = $authorId;
            $closed = $heat['closed'];
			$posttable = $heat['posttableid'] ? "forum_post_{$heat['posttableid']}" : 'forum_post';
			$post = DB::fetch_first("SELECT p.pid, p.message FROM ".DB::table($posttable)." p WHERE p.tid='$tid' AND p.first='1'");
            $tid = $tid;     
			$heat = array_merge($heat, (array)$post);
			$message ="";
			if($limit == 0) {
				break;
			}
			if($messageitems > 0) {
				$message = $heat['message'];
				$message = rtrim($message);
				$msglist = tool::deleMessage($message);
				$message = tool::cut_str($msglist['message'],100,0);
			}
            $UserState = base64_encode(intval($huid)==0?0:get_user_state_by_id($huid));
			$platearray = array("tid"           => base64_encode($tid),
                                "fid"           => base64_encode($Fid),
                                "author"        => base64_encode($author),
                                "authorId"      => base64_encode($authorId),
                                "subject"       => base64_encode($sthreadject),
                                "message"       => base64_encode($message),
                                "views"         => base64_encode($views),
                                "replies"       => base64_encode($replies),
			                    "dateline"      => base64_encode($dateline),
                                "lastpost"      => base64_encode($lastpost),
                                "lastposter"    => base64_encode($lastposter),
                                "mimgurl"       => base64_encode($mimgurl),
                                "closed"        => base64_encode($closed),
                                "userstate"     => base64_encode($UserState)
                                );
			array_push($zjson['list'],$platearray);
			$messageitems--;
			$limit--;
		}
		echo ArrayJSON($zjson);
		$data['expiration'] = TIMESTAMP + $_G['setting']['indexhot']['expiration'];
	}
	save_syscache('heats', $data);
}else{
    $code = base64_encode("1");
	echo "{\"error\":$code}";
}
?>