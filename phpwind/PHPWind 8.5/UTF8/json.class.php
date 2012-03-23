<?php
define('UC_API', strtolower(($_SERVER['HTTPS'] == 'on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/hack'))));

function arrayRecursive(&$array, $function, $apply_to_keys_also = false)  
 {  
     static $recursive_counter = 0;  
     if (++$recursive_counter > 1000) {  
         die('possible deep recursion attack');  
      } 
     foreach ($array as $key => $value) {  
         if (is_array($value)) {  
             arrayRecursive($array[$key], $function, $apply_to_keys_also);  
         } else {  
             $array[$key] = $function($value);  
         }  
 
         if ($apply_to_keys_also && is_string($key)) {  
             $new_key = $function($key);  
             if ($new_key != $key) {  
                 $array[$new_key] = $array[$key];  
                 unset($array[$key]);  
             }  
         }  
     }  
 $recursive_counter--;  
 }  

function ArrayJSON($array) {  
     arrayRecursive($array, 'urlencode', true);
     $json = json_encode($array);  
     return urldecode($json);  
 }
 
function getInfo($uids) {
    if (!$uids) {
        return '';
        }
    require_once(R_P.'require/showimg.php');
    $uids = is_numeric($uids) ? array($uids) : explode(",",$uids);
    $fields = array('icon');
    $baseimgpath = array();
    $userService = L::loadClass('UserService', 'user'); /* @var $userService PW_UserService */
    $users = array();
    foreach ($userService->getByUserIds($uids) as $rt) {
        $baseimgpath = showfacedesign($rt['icon'], 1, 'm');
        break; 
    }
    return '/'.$baseimgpath[0];
}

?>