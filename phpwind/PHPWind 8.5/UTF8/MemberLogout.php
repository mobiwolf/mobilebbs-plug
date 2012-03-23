<?php
require ('../../global.php');
require (R_P.'uc_client/uc_client.php');
header("Content-type: application/json;charset=UTF-8"); 

if($_SERVER['REQUEST_METHOD']=="POST") {
    uc_user_synlogout();
    $ecode = base64_encode("0");
    echo "{\"error\":\"$ecode\"}";
}else {
   $ecode = base64_encode("1");
   echo "{\"error\":\"$ecode\"}";
}

?>
