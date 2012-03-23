<?php
require ('../../global.php');
L::loadClass('forum', 'forum', false);
L::loadClass('post', 'forum', false);
require_once('attupload.php');

header("Content-type: application/json; charset=UTF-8"); 
if($_SERVER['REQUEST_METHOD']=="POST") {
   $binary = base64_decode($_POST['image']);
   $fid =  urldecode($_POST['fid']);
   $uid = urldecode($_POST['uid']);
   
   $tmpname = trim($binary); 
   if (intval($fid)<=0||intval($uid)<=0){
       $ecode = base64_encode("2");
       echo "{\"error\":\"$ecode\"}";
       return ; 
   }
   if (empty($tmpname)||strlen($binary)<=0){
       $ecode = base64_encode("3");
       echo "{\"error\":\"$ecode\"}";
       return ;
    }
   $fp = tmpfile();
   fwrite($fp, $binary);
   $file_info = stream_get_meta_data($fp);
   $tmp_name = $file_info['uri'];
   $filesize = @filesize($tmp_name);

   $_FILES['attachment_1'] = array(
        'name'      => 'weblsq.jpg',
        'type'      => 'image/jpeg',
        'tmp_name'  => $tmp_name,
        'error'     => 0,
        'size'      => $filesize ? $filesize : strlen($request_params[0])
   ); 
  if ($_FILES['attachment_1']['name']) 
      $_FILES['attachment_1']['name'] = to_local($_FILES['attachment_1']['name']);
  global $groupid,$pwforum;
  $pwforum = new PwForum($fid);
  $pwpost  = new PwPost($pwforum);
  $pwpost->forumcheck();
  $pwpost->postcheck();
  if (DbdUpload::getUploadNum()) {
      $att = new AttUpload($uid, $flashatt);
      $att->check();
      $att->transfer();
      DbdUpload::upload($att);
      $aid = $att->getAids();
      $id = base64_encode($aid[0]);
      echo "{\"attachID\":\"$id\"}";
    } 
}else{
    $ecode = base64_encode("1");
    echo "{\"error\":\"$ecode\"}";
}  

function to_local($str)
{
    global $db_charset;
    if (function_exists(pwConvert)) {
        return pwConvert($str, $db_charset, 'utf-8');
    } else {
        return function_exists('mb_convert_encoding') ? @mb_convert_encoding($str, $db_charset, 'UTF-8') : iconv($charset, 'utf-8', $str);
    }
}
?>
