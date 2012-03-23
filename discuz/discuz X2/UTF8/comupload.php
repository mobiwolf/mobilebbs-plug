<?php
 
defined('IN_DISCUZ') or exit; 
require_once libfile('class/upload');
require_once('forumcommon.php');
class comupload extends discuz_upload {
    function is_upload_file($source) {
        return $source && ($source != 'none');
    }
    
    function save_to_local($source, $target) {
        if(!comupload::is_upload_file($source)) {
            $succeed = false;
        }elseif(@copy($source, $target)) {
            $succeed = true;
        }elseif(function_exists('move_uploaded_file') && @move_uploaded_file($source, $target)) {
            $succeed = true;
        }elseif (@is_readable($source) && (@$fp_s = fopen($source, 'rb')) && (@$fp_t = fopen($target, 'wb'))) {
            while (!feof($fp_s)) {
                $s = @fread($fp_s, 1024 * 512);
                @fwrite($fp_t, $s);
            }
            fclose($fp_s); fclose($fp_t);
            $succeed = true;
        }
        
        if($succeed) {
            $this->errorcode = 0;
            @chmod($target, 0644); @unlink($source);
        } else {
            $this->errorcode = 0;
        }
        return $succeed;
    }
}

class forum_upload {

    var $uid;
    var $aid;
    var $simple;
    var $statusid;
    var $attach;
    var $error_sizelimit;

    function forum_upload() {
        global $_G;

        $_G['uid'] = $this->uid = intval($_G['gp_uid']);
        $swfhash = md5(substr(md5($_G['config']['security']['authkey']), 8).$this->uid);
        $this->aid = 0;
        $this->simple = !empty($_G['gp_simple']) ? $_G['gp_simple'] : 0;

        if($_G['gp_hash'] != $swfhash) {
            $this->uploadmsg(10);
        }

        $_G['groupid'] = intval(DB::result_first("SELECT groupid FROM ".DB::table('common_member')." WHERE uid='".$this->uid."'"));
        loadcache('usergroup_'.$_G['groupid']);
        $_G['group'] = $_G['cache']['usergroup_'.$_G['groupid']];

        //require_once libfile('class/upload');

        $upload = new comupload();
        $upload->init($_FILES['Filedata'], 'forum');
        $this->attach = &$upload->attach;

        if($upload->error()) {
            $this->uploadmsg(2);
        }

        $allowupload = !$_G['group']['maxattachnum'] || $_G['group']['maxattachnum'] && $_G['group']['maxattachnum'] > getuserprofile('todayattachs');;
        if(!$allowupload) {
            $this->uploadmsg(6);
        }

        if($_G['group']['attachextensions'] && (!preg_match("/(^|\s|,)".preg_quote($upload->attach['ext'], '/')."($|\s|,)/i", $_G['group']['attachextensions']) || !$upload->attach['ext'])) {
            $this->uploadmsg(1);
        }

        if(empty($upload->attach['size'])) {
            $this->uploadmsg(2);
        }

        if($_G['group']['maxattachsize'] && $upload->attach['size'] > $_G['group']['maxattachsize']) {
            $this->error_sizelimit = $_G['group']['maxattachsize'];
            $this->uploadmsg(3);
        }

        if($type = DB::fetch_first("SELECT maxsize FROM ".DB::table('forum_attachtype')." WHERE extension='".addslashes($upload->attach['ext'])."'")) {
            if($type['maxsize'] == 0) {
                $this->error_sizelimit = 'ban';
                $this->uploadmsg(4);
            } elseif($upload->attach['size'] > $type['maxsize']) {
                $this->error_sizelimit = $type['maxsize'];
                $this->uploadmsg(5);
            }
        }

        if($upload->attach['size'] && $_G['group']['maxsizeperday']) {
            $todaysize = getuserprofile('todayattachsize') + $upload->attach['size'];
            if($todaysize >= $_G['group']['maxsizeperday']) {
                $this->error_sizelimit = 'perday|'.$_G['group']['maxsizeperday'];
                $this->uploadmsg(11);
            }
        }
        updatemembercount($_G['uid'], array('todayattachs' => 1, '' => $upload->attach['size']));
        $upload->save();
        if($upload->error() == -103) {
            $this->uploadmsg(8);
        } elseif($upload->error()) {
            $this->uploadmsg(9);
        }
        $thumb = $remote = $width = 0;
        if($_G['gp_type'] == 'image' && !$upload->attach['isimage']) {
            $this->uploadmsg(7);
        }
        if($upload->attach['isimage']) {
            if($_G['setting']['thumbstatus']) {
                require_once libfile('class/image');
                $image = new image;
                $thumb = $image->Thumb($upload->attach['target'], '', $_G['setting']['thumbwidth'], $_G['setting']['thumbheight'], $_G['setting']['thumbstatus'], $_G['setting']['thumbsource']) ? 1 : 0;
                $width = $image->imginfo['width'];
            }
            if($_G['setting']['thumbsource'] || !$_G['setting']['thumbstatus']) {
                list($width) = @getimagesize($upload->attach['target']);
            }
        }
        if($_G['gp_type'] != 'image' && $upload->attach['isimage']) {
            $upload->attach['isimage'] = -1;
        }
        $this->aid = $aid = getattachnewaid($this->uid);
        DB::query("INSERT INTO ".DB::table('forum_attachment_unused')." (aid, dateline, filename, filesize, attachment, isimage, uid, thumb, remote, width)
            VALUES ('$aid', '$_G[timestamp]', '".$upload->attach['name']."', '".$upload->attach['size']."', '".$upload->attach['attachment']."', '".$upload->attach['isimage']."', '".$this->uid."', '$thumb', '$remote', '$width')");
        $this->uploadmsg(0);
    }

    function uploadmsg($statusid) {
         if (intval($statusid)>0) 
         $ecode = base64_encode("$statusid");
         if ($ecode!="")
           echo "{\"error\":\"$ecode\"}";
    }
} 
  
?>
