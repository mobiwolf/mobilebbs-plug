<?php

require_once ('../../global.php');
include_once(R_P.'data/bbscache/forum_cache.php');
require_once(R_P.'require/common.php');
require_once ('json.class.php');
header("Content-type: application/json; charset=utf-8"); 
if($_SERVER['REQUEST_METHOD']=="GET") {
    $topics = $article = $tposts = 0;
    $newpic = (int)GetCookie('newpic');
    $forum_tree = array();
    $sqlwhere = isset($db_showcms) && $db_showcms ? '' : " AND f.cms!='1'";
    $tabPre = $db->dbpre;
    $query  = $db->query("SELECT *  FROM ".$tabPre."forums f LEFT JOIN ".$tabPre."forumdata fd USING(fid) WHERE f.ifcms!=2  $sqlwhere  order by f.vieworder");
    while ($forums = $db->fetch_array($query)) {
        if ($forums['type'] != 'category') {
            $forums['topics'] = $forums['topic']+$forums['subtopic'];
                if ($db_topped) {
                    $forums['topics'] += $forums['top1'];
                    $forums['article'] += $forums['top1'];
                }
                $article += $forums['article'];
                $topics += $forums['topics'];
                $tposts += $forums['tpost'];
                $forums['au'] = $forums['admin'] = '';
                $forums['new'] = false;
                if (!$forums['password'] && (!$forums['allowvisit'] || allowcheck($forums['allowvisit'],$groupid, $winddb['groups'],$forums['fid'],$winddb['visit']))) {
                    list($forums['t'],$forums['au'],$forums['newtitle'],$forums['ft']) = $forums['lastpost'] ? explode("\t",$forums['lastpost']) : array('','','','');
                    $forums['pic'] = $newpic < $forums['newtitle'] && ($forums['newtitle'] + $db_newtime > $timestamp) ? 'new' : 'old';
                    $forums['newtitle'] = get_date($forums['newtitle']);
                    $forums['t'] = substrs($forums['t'],26);
                } elseif ($forum[$forums['fid']]['f_type'] === 'hidden' && $groupid != 3) {
                    if ($forums['password'] && allowcheck($forums['allowvisit'],$groupid,$winddb['groups'], $forums['fid'],$winddb['visit'])) {
                        $forums['pic'] = 'lock';
                    } else {
                        continue;
                        }
                } else {
                    $forums['pic'] = 'lock';
                }
        
                if ($db_indexfmlogo == 2) {
                    if(!empty($forums['logo']) && strpos($forums['logo'],'http://') === false && file_exists($attachdir.'/'.$forums['logo'])){
                        $forums['logo'] = "$db_bbsurl/$attachpath/$forums[logo]";
                        }
                } elseif ($db_indexfmlogo == 1 && file_exists(R_P."$imgdir/$stylepath/forumlogo/$forums[fid].gif")) {
                    $forums['logo'] = "$db_bbsurl/$imgpath/$stylepath/forumlogo/$forums[fid].gif";
                } else {
                    $forums['logo'] = '';
                    } 
               $forum_tree[$forums['fid']] = arrayResult($forums);
               
        } else {
            $forums['pic'] = 'old';
            //array_push($forum_tree[$forums['fid']],$sub_forum); 
            //$forum_tree[$forums['fid']] = $sub_forum ;       
            $forum_tree[$forums['fid']] = arrayResult($forums);
            }
    }
    
    $db->free_result($query);
    foreach($forum_tree as $fid => $sub_forum) {
        if (base64_decode($sub_forum['type']) == 'sub2') {
            array_push($forum_tree[base64_decode($sub_forum['fup'])]['child'],$sub_forum);
            //$forum_tree[$sub_forum['fup']]['child'] = $sub_forum;
            unset($forum_tree[$fid]);
        }
    }

    foreach($forum_tree as $fid => $sub_forum) {
        if (base64_decode($sub_forum['type']) == 'sub') {
            array_push($forum_tree[base64_decode($sub_forum['fup'])]['child'],$sub_forum);
            //$forum_tree[$sub_forum['fup']]['child'] = $sub_forum;
            unset($forum_tree[$fid]);
        }
    }

    foreach($forum_tree as $fid => $sub_forum) {
        if (base64_decode($sub_forum['type']) == 'forum') {
            array_push($forum_tree[base64_decode($sub_forum['fup'])]['child'],$sub_forum);
            //$forum_tree[$sub_forum['fup']]['child'] = $sub_forum;
            unset($forum_tree[$fid]);
            }
        }
        
    foreach($forum_tree as $key=>$value){
        if($forum_tree[$key]['type'] == 'category' && $forum_tree[$key]['child'] == ''){
            unset($forum_tree[$key]);
        }elseif(!(array_key_exists('fid', $forum_tree[$key]))){
            unset($forum_tree[$key]);
        }
    }
    $zjson = array("list"=>procArray($forum_tree)) ;
    echo ArrayJSON($zjson);
}
    
function arrayResult(&$forum)
{
    $Threads = $forum['topic'];
    $postsCountnum=20; 
    $postsCountnum=$postsCountnum*2;
    $sumpage=$Threads/$postsCountnum;
    $sumpage=intval($sumpage+1);
             
    $arr_forum = array( "fid" => base64_encode($forum['fid']), 
                        "fup" => base64_encode($forum['fup']),
                        "name" => base64_encode(iconv("gb2312","utf-8",$forum['name'])), 
                        //"threads" => $forum['topic'],
                        "posts" => base64_encode(iconv("gb2312","utf-8",$forum['article'])),
                        "todayposts" => base64_encode($forum['tpost']),
                        "type" => base64_encode($forum['type']),
                        //"sumpage" => "$sumpage",
                        "child" =>array()
    );
    return $arr_forum;
}

function procArray(&$arr)
{
    $pArray = array();
    foreach($arr as $key=>$value){
        array_push($pArray,$value);
    }
    return $pArray;
} 
  
?>
