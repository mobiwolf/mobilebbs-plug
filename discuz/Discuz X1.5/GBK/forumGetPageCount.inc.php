<?php
header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="GET") {
	$httpFid = base64_decode($_SERVER['HTTP_FID']);
	$httpTid = base64_decode($_SERVER['HTTP_TID']);
	$Fid =intval($httpFid)>0?intval($httpFid):0;
	$Tid =intval($httpTid)>0?intval($httpTid):0;
    $ecode = base64_encode("2");
	if ($Fid>0){
		$count = base64_encode(DB::result_first("SELECT  count(*) FROM ".DB::table('forum_thread')." where fid=$Fid"));
	}elseif ($Tid>0) {
		$count = base64_encode(DB::result_first("SELECT  count(*) FROM ".DB::table('forum_post')." where tid=$Tid"));
	}else{
		echo "{\"error\":\"$ecode\"}";return ;
	}
	echo "{\"count\":\"$count\"}";
}else {
    $ecode = base64_encode("1");
	echo "{\"error\":\"$ecode\"}";
}

?>