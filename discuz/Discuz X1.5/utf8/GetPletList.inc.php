<?php

defined('IN_DISCUZ') or exit;
require_once libfile('function/forumlist');
require 'json.class.php';
header("Content-type: application/json; charset=UTF-8 "); 

if($_SERVER['REQUEST_METHOD']=="GET") {
$sql = !empty($_G['member']['accessmasks']) ?
    "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, f.lastpost, f.inheritedmod, f.domain,
        f.forumcolumns, f.simple, ff.description, ff.moderators, ff.icon, ff.viewperm, ff.redirect, ff.extra, ff.password, a.allowview,
        hf.favid
        FROM ".DB::table('forum_forum')." f
        LEFT JOIN ".DB::table('forum_forumfield')." ff ON ff.fid=f.fid
        LEFT JOIN ".DB::table('forum_access')." a ON a.uid='$_G[uid]' AND a.fid=f.fid
        LEFT JOIN ".DB::table('home_favorite')." hf ON hf.uid='$_G[uid]' AND hf.id=f.fid AND hf.idtype='fid'
        WHERE f.status='1' ORDER BY f.type, f.displayorder"
    : "SELECT f.fid, f.fup, f.type, f.name, f.threads, f.posts, f.todayposts, f.lastpost, f.inheritedmod, f.domain,
        f.forumcolumns, f.simple, ff.description, ff.moderators, ff.icon, ff.viewperm, ff.redirect, ff.extra, ff.password,
        hf.favid
        FROM ".DB::table('forum_forum')." f
        LEFT JOIN ".DB::table('forum_forumfield')." ff USING(fid)
        LEFT JOIN ".DB::table('home_favorite')." hf ON hf.uid='$_G[uid]' AND hf.id=f.fid AND hf.idtype='fid'
        WHERE f.status='1' ORDER BY f.type, f.displayorder";

$query = DB::query($sql);
$forum_root = array(0 => array('fid' => 0, 'child' => array()));
$forum_g = $froum_f = $forum_s = array();
while($forum = DB::fetch($query)) {
    
    if ($forum['type'] != 'group') {
        $forum_icon = $forum['icon'];
        if(forum($forum)) {
            $forum['icon'] = get_forumimg($forum_icon);
        } else {
            continue;
        }
    }
    
    switch ($forum['type'])
    {
        case   'sub': $forum_s[] = $forum; break;
        case 'group': $forum_g[] = $forum; break;
        case 'forum': $froum_f[] = $forum; break;
    }

}

foreach($forum_s as $s_forum) {
    insert_forum($froum_f, $s_forum);
}

foreach($froum_f as $f_forum) {
    insert_forum($forum_g, $f_forum);
}

foreach($forum_g as $g_forum) {
    if ($g_forum['child']) {
        insert_forum($forum_root, $g_forum);
    }
}

 $forum_tree = $forum_root[0]['child'];
 $result = array("list"=>$forum_tree);
 echo ArrayJSON($result);
} 

function insert_forum(&$forum_ups, $forum)
{
    global $_G;
    
    if ($forum['type'] == 'group' && !isset($forum['child'])) return;
    foreach($forum_ups as $id => $forum_up)
    {
        if ($forum_up['fid'] == $forum['fup'])
        {
            $forum_ups[$id]['todayposts'] += $forum['todayposts'];
            $subforumonly = $forum['simple'] & 1;
            if($forum['icon'] && strpos($forum['icon'], "ttp://"))
            {
                 $forum['icon'] = $forum['icon'];
            }else if($forum['icon'])
            {
                $forum['icon'] =  $_G['setting']['discuzurl'].'/'.$forum['icon'];  
            }else
            {
                $forum['icon'] = "";   
            }
             $Threads = $forum['threads'];
             $postsCountnum=$_G['tpp']; 
             $postsCountnum=$postsCountnum*2;
             $fid = $forum['fid'];
             $name = $forum['name'];
             $threads = $forum['threads'];
             $post = $forum['posts'];
             $toy = $forum['todayposts'];
             $arr_forum = array("fid"           => base64_encode($fid), 
                                 "name"         => base64_encode($name), 
                                 //"threads"    => base64_encode($threads),
                                 "posts"        => base64_encode($post),
                                 "todayposts"   => base64_encode($toy),
                                 //"sumpage"    => base64_encode($sumpage),
                                 "child"        => ""
             );
             
            if (isset($forum['child']) && !empty($forum['child'])){
                $arr_forum['child'] = $forum['child'];
			}else
                $arr_forum['child'] = array();
            $forum_ups[$id]['child'][] = $arr_forum;
            continue;
        }
    }
}


?>
