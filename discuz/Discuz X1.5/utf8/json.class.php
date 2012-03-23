<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

class Json{
	
	public static function encode($str){
		$code = urlencode($str);
		$code = json_encode($str);
		return preg_replace("#\\\u([0-9a-f]+)#ie", "iconv('UCS-2', 'UTF-8', pack('H4', '\\1'))", $code);
    }
	public static function decode($str){
		return json_decode($str);
	}
}

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
?>