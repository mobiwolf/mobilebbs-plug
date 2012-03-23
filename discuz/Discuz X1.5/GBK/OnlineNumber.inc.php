<?php

defined('IN_DISCUZ') or exit;
header("Content-type: application/json; charset=utf-8"); 

if($_SERVER['REQUEST_METHOD']=="GET") {
    $theurl = "home.php?mod=space&uid=$space[uid]&do=friend&view=online";
    $actives = array('me'=>' class="a"');
    space_merge($space, 'field_home');
    $wheresql = '';
    $theurl = "home.php?mod=space&uid=$space[uid]&do=friend&view=online&type=member";
    $wheresql = " WHERE uid>0";

    $count = DB::result(DB::query("SELECT COUNT(*) FROM ".DB::table('common_session')." $wheresql"), 0);

    if(empty($_G['cookie']['onlineusernum'])) {
        $onlinenum = DB::result_first("SELECT count(*) FROM ".DB::table('common_session'));
    } else {
        $onlinenum = intval($_G['cookie']['onlineusernum']);
    }

    if($count) {
        $query = DB::query("SELECT * FROM ".DB::table("common_session")." $wheresql AND invisible='0' ORDER BY lastactivity DESC ");
        while($value = DB::fetch($query)) {

            if($value['magichidden']) {
                $count = $count - 1;
                continue;
            }
            if($_GET['type']=='near') {
                if($value['uid'] == $space['uid']) {
                    $count = $count-1;
                    continue;
                }
            }

            if(!$value['invisible']) $ols[$value['uid']] = $value['lastactivity'];
            $list[$value['uid']] = $value;
            $fuids[$value['uid']] = $value['uid'];
        }

        if($fuids) {
            require_once libfile('function/friend');
            friend_check($space['uid'], $fuids);

            $query = DB::query("SELECT cm.*, cmfh.* FROM ".DB::table("common_member").' cm
                LEFT JOIN '.DB::table("common_member_field_home")." cmfh ON cmfh.uid=cm.uid
                WHERE cm.uid IN(".dimplode($fuids).")");
            while($value = DB::fetch($query)) {
                $value['isfriend'] = $value['uid']==$space['uid'] || $_G["home_friend_".$space['uid'].'_'.$value['uid']] ? 1 : 0;
                $list[$value['uid']] = array_merge($list[$value['uid']], $value);
            }
        }
    }
    $multi = multi($count, $perpage, $page, $theurl);
    if($fuids) {
        $query = DB::query("SELECT * FROM ".DB::table('common_session')." WHERE uid IN (".dimplode($fuids).")");
        while ($value = DB::fetch($query)) {
            if(!$value['magichidden'] && !$value['invisible']) {
                $ols[$value['uid']] = $value['lastactivity'];
            } elseif($list[$value['uid']] && !in_array($_GET['view'], array('me', 'trace', 'blacklist'))) {
                unset($list[$value['uid']]);
                $count = $count - 1;
            }
        }
        if($_GET['view'] != 'me') {
            require_once libfile('function/friend');
            friend_check($fuids);
        }
        $query = DB::query("SELECT cm.*, cmfh.* FROM ".DB::table("common_member").' cm LEFT JOIN '.DB::table("common_member_field_home")." cmfh ON cmfh.uid=cm.uid WHERE cm.uid IN(".dimplode($fuids).")");
        while($value = DB::fetch($query)) {
            $value['isfriend'] = $value['uid']==$space['uid'] || $_G["home_friend_".$space['uid'].'_'.$value['uid']] ? 1 : 0;
            if(empty($list[$value['uid']])) $list[$value['uid']] = array();
            $list[$value['uid']] = array_merge($list[$value['uid']], $value);
        }
    }

    $navtitle = lang('core', 'title_friend_list');
    $navtitle = lang('space', 'sb_friend', array('who' => $space['username']));
    $metakeywords = lang('space', 'sb_friend', array('who' => $space['username']));
    $metadescription = lang('space', 'sb_share', array('who' => $space['username']));

    $guest = max($onlinenum - $count, 0);
    $count = base64_encode("$count");
    $guest = base64_encode("$guest");
    $onlinenum = base64_encode("$onlinenum");
    echo "{\"list\":[{\"membercount\":\"$count\",\"guestcount\":\"$guest\",\"onlineuser\":\"$onlinenum\"}]}";
       
    }else{
        $ecode = base64_encode("1");
        echo "{\"error\":\"$ecode\"}";//´«Êä¸ñÊ½´íÎóPOST/GET
}

?>
