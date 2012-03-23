<?php

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}
header("Content-type: application/json; charset=UTF-8 "); 
if($_SERVER['REQUEST_METHOD']=="GET") {
    $_G['gp_uid'] = $_G['uid'] = 17;
    $_G['gp_hash'] = md5(substr(md5($_G['config']['security']['authkey']), 8).$_G['uid']);
    require_once 'comupload.php';
    
    $fp = tmpfile();
    fwrite($fp,file_get_contents('d:/11.jpg'));
    $file_info = stream_get_meta_data($fp);
    $tmp_name = $file_info['uri'];
    $filesize = @filesize($tmp_name);
    $filetype = 'JPG';
    $ran =rand(10000,99999);
 
    $_FILES['Filedata'] = array(
        'name'      => 'weblsq'.$ran.'.jpg',
        'type'      => $filetype == 'JPG' ? 'image/jpeg' : 'image/png',
        'tmp_name'  => $tmp_name,
        'error'     => 0,
        'size'      => $filesize ? $filesize : 0
        );
    if(empty($_G['gp_simple'])) {
        $_FILES['Filedata']['name'] = addslashes(diconv(urldecode($_FILES['Filedata']['name']), 'UTF-8'));
        $_FILES['Filedata']['type'] = $_G['gp_filetype'];
    }
    $upload = new forum_upload(); 
    if(file_exists($tmp_name)){
       @unlink($tmp_name);
     }
    $attachID = base64_encode($upload->aid);
    echo "{\"attachID\":\"$attachID\"}";
    
}
else{
   $ecode = base64_encode("1");
   echo "{\"error\":\"$ecode\"}";
   }

?>
