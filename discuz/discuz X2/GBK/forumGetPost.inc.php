<?php

require 'json.class.php';
require 'tools.class.php';
require 'forumcommon.php';
require_once libfile('function/forum');
header("Content-type: application/json; charset=utf-8"); 
if($_SERVER['REQUEST_METHOD']=="GET") {
    $httpTid = base64_decode($_SERVER['HTTP_TID']);
    $httpPage= base64_decode($_SERVER['HTTP_PAGE']);
    $httpmodel=base64_decode($_SERVER['HTTP_MODEL']);                                                                  
    $httpsize =base64_decode($_SERVER['HTTP_PAGESIZE']);
	$huid = trim(base64_decode($_SERVER['HTTP_UID']));
    $Tid = intval($httpTid)>0?intval($httpTid):0;
    $Page = intval($httpPage)>0?intval($httpPage):0;
    $Model = intval($httpmodel)>0?intval($httpmodel):0;
    $size = intval($httpsize)>0?intval($httpsize):10;                                                                  $Model =$httpmodel!=NULL&&$httpmodel!=""?$httpmodel:($getmodel!=NULL&&$getmodel!=""?$getmodel:0);
    if ($Tid==0) {
      $ecode = base64_encode("2");
      echo "{\"error\":\"$ecode\"}";
      return ;
    }
    $frontnumber = ($Page*$size);
    $zjson['list']=array();
    $sql = "SELECT * FROM ".DB::table('forum_post')." where tid='$Tid' and invisible=0 order by dateline LIMIT $frontnumber, $size ";
    $query = DB::query($sql);
    $lou = 1+$frontnumber;
    while($view = DB::fetch($query)) {
        $pid = $view['pid'];
        $tid = $view['tid'];
        $fid = $view['fid'];
        $first = $view['first'];
        $author = $view['author'];
        $authorId = $view['authorid'];
        $subject = $view['subject'];
        $message = rtrim($view['message']);
        $dateline = tool::dgmtimedate($view['dateline'],'u');
        $mimgurl = discuz_uc_avatar($authorId,'middle',TRUE);
        $authorId = $authorId;
        $invisible = $view['invisible'];
        $status = $view['status'];
        $replies = DB::result_first("SELECT  count(*) FROM ".DB::table('forum_post')." where tid='$Tid' ");
        $Uid = $_G['uid'];
        $msg = "";
        $quotemsg1 = explode('[quote]',$message);
        if (count($quotemsg1)>1){
            $quotemsg2 = explode('[/quote]',$quotemsg1[1]);
            $msg = "[quote]".$quotemsg2[0]."[/quote]";
            $message = $quotemsg2[1];
        }
        if($Model==0){
            preg_match_all('/\[attach\](.*?)\[\/attach\]/i', $message,  $attach);
            $attcount=count(explode('[attach]',$message));
            if ($attcount>=2){
                $attachcount=$attach[1];
                for ($atti=0;$atti<($attcount-1);$atti++){
                $aid = $attachcount[$atti];
                $sql = "SELECT * FROM ".DB::table('forum_attachment')." where aid=$aid";
                $attquery = DB::query($sql);
                    if($attrequ = DB::fetch($attquery)) {
                        $atttableid = $attrequ['tableid'];
                        $attaid=$attrequ['aid'];
                        $attnumquery = DB::query("SELECT * FROM ".DB::table('forum_attachment_'.$atttableid)." where aid='$attaid'");
                        if($attnumrequ = DB::fetch($attnumquery)) {
                            $attimg= UC_API."/"."data/attachment/forum/".$attnumrequ['attachment'];
                            $attachm= tool::deletetime("[attach]$attaid [/attach]");
                            $attachimg= "";
                            $attachm = trim(ereg_replace(" ","",$attachm));
                            $attachimg = trim(ereg_replace(" ","",$attachimg));
                            $message= str_replace($attachm,$attachimg,$message);
                        }
                    }
                }
            }
            //$msg = tool::discuzcode($msg, 0);
            $message = tool::discuzcode($message, 0);
            //$msg = iconv("gb2312","utf-8",strip_tags(tool::deletetime($msg,0),""));
            $msg = tool::strip_ubb(iconv("gb2312","utf-8",$msg));
            $message = iconv("gb2312","utf-8",tool::deletetime($message,$Model));
        }elseif ($Model==1){
            //$message = urldecode(tool::parsesmiles($message,$Model));
            preg_match_all('/\[attach\](.*?)\[\/attach\]/i', $message,  $attach);
            $attcount=count(explode('[attach]',$message));
            if ($attcount>=2){
                $attachcount=$attach[1];
                for ($atti=0;$atti<($attcount-1);$atti++){
                $aid = $attachcount[$atti];
                $sql = "SELECT * FROM ".DB::table('forum_attachment')." where aid=$aid";
                $attquery = DB::query($sql);
                    if($attrequ = DB::fetch($attquery)) {
                        $atttableid = $attrequ['tableid'];
                        $attaid=$attrequ['aid'];
                        $attnumquery = DB::query("SELECT * FROM ".DB::table('forum_attachment_'.$atttableid)." where aid='$attaid'");
                        if($attnumrequ = DB::fetch($attnumquery)) {
                            $attimg= UC_API."/"."data/attachment/forum/".$attnumrequ['attachment'];
                            $attachm= tool::deletetime("[attach]$attaid [/attach]");
                            if (intval($attnumrequ['isimage'])!=0){
                              $attachimg= tool::deletetime("[img=0,0]$attimg [/img]");
                            }else{
                              $filename = $attnumrequ['filename']; 
                              $attachimg= tool::deletetime("[url=$attimg]$filename [/url]");  
                            }
                            $attachm = trim(ereg_replace(" ","",$attachm));
                            $attachimg = trim(ereg_replace(" ","",$attachimg));
                            $message= str_replace($attachm,$attachimg,$message);
                        }
                    }
                }
            }
            //$msg = tool::discuzcode($msg, 1);
            $message = tool::discuzcode($message,1);
            //$msg = iconv("gb2312","utf-8",strip_tags(tool::deletetime($msg,0),""));
            $msg = tool::strip_ubb(iconv("gb2312","utf-8",$msg));
            //$message = iconv("gb2312","utf-8",tool::deletetime($message,$Model));
            $message = tool::strip_ubb(iconv("gb2312","utf-8",$message));
        }
        $flow = $lou;
        $UserState = get_user_state_by_id($authorId);
        $platearray = array("pid"       => base64_encode($pid),
                            "author"    => base64_encode(iconv("gb2312","utf-8",$author)),
                            "authorid"  => base64_encode($authorId),
                            "msg"       => base64_encode($msg),
                            "message"   => base64_encode($message),
                            "dateline"  => base64_encode(iconv("gb2312","utf-8",$dateline)),
                            "mimgurl"   => base64_encode($mimgurl),
                            "status"    => base64_encode($status),
                            "lou"       => base64_encode($flow),
                            "replies"   => base64_encode($replies),
                            "userstate" => base64_encode($UserState)
                            );
        array_push($zjson['list'],$platearray);
        $lou ++;
    }
        echo ArrayJSON($zjson);
}
else {
    $ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}
?>
