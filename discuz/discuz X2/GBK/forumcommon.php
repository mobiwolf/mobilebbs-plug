<?php

defined('IN_DISCUZ') or exit;

function unescape_htmlentitles($str) {
    preg_match_all("/(?:%u.{4})|.{4};|&#\d+;|.+|\\r|\\n/U",$str,$r);
    $ar = $r[0];
    
    foreach($ar as $k=>$v) {
        if(substr($v,0,2) == "&#") {
            $ar[$k] =@html_entity_decode($v,ENT_QUOTES, 'UTF-8');
        }
    }
    return join("",$ar);
}

function format_time($time)
{    
    return iso8601_encode(strtotime($time));
}

function parameter_to_local(&$param)
{
    foreach($param as $key => $value) {
        if (is_array($value)) {
            parameter_to_local($param[$key]);
        } elseif (strpos($key, 'gp_') === 0) {
            to_local($param[$key]);
        }
    }
}

function to_local(&$str)
{
    if (function_exists('diconv'))
    {
        $str = diconv($str, 'utf-8');
    }elseif (function_exists('mb_convert_encoding'))
    {
        $str = @mb_convert_encoding($str, CHARSET, 'UTF-8');
    }else
    {
        $str = iconv('utf-8', CHARSET, $str);
    }
    return $str;
}

function to_utf8($str)
{
    if (function_exists('diconv'))
        $str = diconv($str, CHARSET, 'utf-8');
    elseif (function_exists('mb_convert_encoding'))
        $str = @mb_convert_encoding($str, 'UTF-8', CHARSET);
    else
        $str = iconv(CHARSET, 'utf-8', $str);
    
    return unescape_htmlentitles($str);
}

function iso8601_encode($timet)
{
    static $offset;
    if($offset === null) {
        $offset = getglobal('member/timeoffset');
    }
    
    $t = gmdate("Ymd\TH:i:s", $timet + $offset * 3600);      
    $t .= sprintf("%+03d:%02d", intval($offset), abs($offset - intval($offset)) * 60); 
    
    return $t;
}

function get_user_avatar($uid)
{
    return html_entity_decode(avatar($uid, 'small', true));
}

function process_page($start_num, $end)
{
    global $start, $limit;
    $start = intval($start_num);
    $end = intval($end);
    $start = empty($start) ? 0 : max($start, 0);
    $end = (empty($end) || $end < $start) ? ($start + 19) : max($end, $start);
    if ($end - $start >= 50) {
        $end = $start + 49;
    }
    $limit = $end - $start + 1;
}

function get_message($message)
{
    $message = preg_replace('/\[\/?code\]|\[\/?b\]/', '', $message);
    $message = process_img_to_code($message);
    $message = preg_replace('/\[img\].*?\/images\/smilies\/.*?\[\/img\]/', '', $message);
    return basic_clean($message);
}

function get_short_content($tid, $posttable, $length = 100)
{
    $message = DB::result_first("SELECT message FROM ".DB::table($posttable)." WHERE tid='$tid' AND first='1' AND invisible='0'");
    $message = get_short_message($message);
    return $message;
}

function get_anonymous($tid, $posttable, $length = 100)
{
    $message = DB::result_first("SELECT anonymous FROM ".DB::table($posttable)." WHERE tid='$tid' AND first='1' AND invisible='0'");
    return $message;
}

function get_short_message($message, $length = 100)
{
    $message = preg_replace('/\[url.*?\].*?\[\/url\]/si', '###url###', $message);
    $message = preg_replace('/\[img.*?\].*?\[\/img\]/si', '###img###', $message);
    $message = preg_replace('/\[attach.*?\].*?\[\/attach\]/si', '###attach###', $message);
    $message = preg_replace('/\[(i|code|quote|free|media|audio|flash|hide|swf).*?\].*?\[\/\\1\]/si', '', $message);
    $message = preg_replace('/\[.*?\]/si', '', $message);
    $message = preg_replace('/###(url|img|attach)###/si', '[$1]', $message);
    $message = preg_replace('/^\s*|\s*$/', '', $message);
    $message = preg_replace('/[\n\r\t]+/', ' ', $message);
    $message = preg_replace('/\s+/', ' ', $message);
    $message = cutstr($message, $length);
    $message = basic_clean($message);

    return $message;
}

function get_error($err_key, $replace_array = array(), $need_login = false)
{
    global $_G;
    
    include(DISCUZ_ROOT.'/source/language/lang_message.php');
    
    header('forum_is_login:'.($_G['uid'] ? 'true' : 'false'));

    $err_id = (!$_G['uid'] && $need_login) ? 20 : 18;
    $err_str = isset($lang[$err_key]) ? $lang[$err_key] : $err_key;
    
    foreach($replace_array as $key => $value)
    {
        $err_str = str_replace('{'.$key.'}', $value, $err_str);
    }
    
    if ($err_str == 'param_error')
        $error_message = '参数错误';
    elseif (strpos($err_str, 'upload_error') !== false)
        $error_message = str_replace('upload_error', '上传失败', $err_str);
    else
        $error_message = basic_clean($err_str);
    
    $r = array(
                'result'        => false,
                'result_text'   => $error_message
          );
    echo $error_message;
    exit;
}

function log_it($log_data)
{
    global $mobiquo_config;

    if(!$mobiquo_config['keep_log'] || !$log_data)
    {
        return;
    }

    $log_file = './log/'.date('Ymd_H').'.log';

    file_put_contents($log_file, print_r($log_data, true), FILE_APPEND);
}

function post_html_clean($str)
{
    $search = array(
        '/\[img\](.*?)\[\/img\]/sei',  
        '/<img .*?src="(.*?)".*?\/?>/sei',  
        '/<a .*?href="(.*?)".*?>(.*?)<\/a>/sei',
        '/<br\s*\/?>|<\/cite>|<li>|<\/em>|<em.*?>|<\/(h|H)\d>/si',
        '/&nbsp;/si',
        '/<strong>(.*?)<\/strong>/si',
        '/<b>(.*?)<\/b>/si',
        '/<i>(.*?)<\/i>/si',
        '/<u>(.*?)<\/u>/si',
        '/<font color="(.*?)">(.*?)<\/font>/si',
    );

    $replace = array(
        "'[img]'.url_encode('$1').'[/img]'",
        "'[img]'.url_encode('$1').'[/img]'",
        "url_check('$1', '$2')",
        "\n",
        ' ',
        '[b]$1[/b]',
        '[b]$1[/b]',
        '[i]$1[/i]',
        '[u]$1[/u]',
        '[color=$1]$2[/color]',
    );
    
    $str = preg_replace('/\n|\r/si', '', $str);
    $str = parse_quote($str);
    $str = preg_replace('/<div class="y".*?>.*?<\/div>(<br\s*\/>){0,2}/', '', $str);
    $str = preg_replace('/<em class="xg1".*?>.*?<\/em>/', '', $str);
    $str = preg_replace('/<div class="tip_c xs0".*?>.*?<\/div>(<br\s*\/>){0,2}/', '', $str);
    $str = preg_replace('/<span class="xs0".*?>.*?<\/span>(<br\s*\/>){0,2}/', '', $str);
    $str = preg_replace('/<script.*?>.*?<\/script>/', '', $str);
    $str = preg_replace('/<div class="tip tip_4".*?>.*?<\/div>(<br\s*\/>){0,2}/', '', $str);
    $str = preg_replace($search, $replace, $str);

    // remove link on img
    $str = preg_replace('/\[url=.*?\](\[img\].*?\[\/img\])\[\/url\]/', '$1', $str);
    // remove reply link
    $str = preg_replace('/\[url=[^\]]*?redirect\.php\?goto=findpost.*?\](.*?)\[\/url\]/', '$1', $str);
    // Currently, we don't display smiles and system image
    $str = preg_replace('/\[img\]images\/(smilies|default)\/.*?\[\/img\]/si', '', $str);
    // Currently, we don't display back image
    $str = preg_replace('/\[img\].*?back\.gif\[\/img\]/si', '', $str);
    $str = preg_replace('/\[img\][^\[]*?drc_url\/image\/safe.png\[\/img\]/si', '', $str);
    //$str = preg_replace('/\[img\](.*?)\[\/img\]/', '', $str);
    $str = preg_replace('/{:soso_(.*?):}/sei', "soso_smiles('$1')", $str);
    $str = basic_clean($str);
    
    return parse_bbcode($str);
}

function parse_bbcode($str)
{
    global $return_html;
    
    $search = array(
        '#\[(b)\](.*?)\[/b\]#si',
        '#\[(u)\](.*?)\[/u\]#si',
        '#\[(i)\](.*?)\[/i\]#si',
        '#\[color=(\#[\da-fA-F]{3}|\#[\da-fA-F]{6}|[A-Za-z]{1,20}|rgb\(\d{1,3}, ?\d{1,3}, ?\d{1,3}\))\](.*?)\[/color\]#si',
    );
    
    if ($return_html) {
        $str = htmlspecialchars($str);
        $replace = array(
            '<$1>$2</$1>',
            '<$1>$2</$1>',
            '<$1>$2</$1>',
            '<font color="$1">$2</font>',
        );
        $str = str_replace("\n", '<br />', $str);
    } else {
        $replace = '$2';
    }
    
    return preg_replace($search, $replace, $str);
}

function soso_smiles($smilieid)
{
    if(strpos($smilieid, '_') === 0) {
        $realsmilieid = $smiliekey = substr($smilieid, 0, -2);
        $serverid = intval(substr($smilieid, -1));
        return "[img]http://piccache{$serverid}.soso.com/face/{$realsmilieid}[/img]";
    } elseif(strpos($smilieid, 'e') === 0) {
        return "[img]http://cache.soso.com/img/img/{$smilieid}.gif[/img]";
    } else {
        return "{:soso_$smilieid:}";
    }
}
function parse_quote($str)
{
    $blocks = preg_split('/(<blockquote.*?>|<\/blockquote>)/i', $str, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
    
    $quote_level = 0;
    $message = '';
        
    foreach($blocks as $block)
    {
        if (preg_match('/<blockquote.*?>/i', $block)) {
            if ($quote_level == 0) $message .= '[quote]';
            $quote_level++;
        } else if (preg_match('/<\/blockquote>/i', $block)) {
            if ($quote_level <= 1) $message .= '[/quote]';
            if ($quote_level >= 1) {
                $quote_level--;
                $message .= "\n";
            }
        } else {
            if ($quote_level <= 1) $message .= $block;
        }
    }
    
    return $message;
}

function url_check($url, $data)
{
    if (preg_match('/^\s*http/', $url))
        return "[url=$url]${data}[/url]";
    else
        return $data;
}

function url_encode($url)
{
    global $_G;
    
    if (!$url) return '';
    
    $url = rawurlencode($url);
    
    $from = array('/%3A/', '/%2F/', '/%3F/', '/%2C/', '/%3D/', '/%26/', '/%25/', '/%23/', '/%2B/', '/%3B/');
    $to   = array(':',     '/',     '?',     ',',     '=',     '&',     '%',     '#',     '+',     ';');
    $url = preg_replace($from, $to, $url);
    $url = preg_replace('/http:.*?http:/', 'http:', $url);
    
    if (!preg_match('/http:/', $url))
    {
        $url = $_G['setting']['discuzurl'].'/'.$url;
    }
    
    return html_entity_decode($url);
}

function basic_clean($str)
{
    $str = strip_tags($str);
    $str = trim($str);
    $str = to_utf8($str);
    return html_entity_decode($str, ENT_QUOTES, 'UTF-8');
}

function get_user_id_by_name($username)
{
    global $_G, $uc_db;
    
    if (!$username) return '';
    
    $var = "my_get_name_$username";
    if(!isset($GLOBALS[$var])) {
        if($username == $GLOBALS['member']['username']) {
            $GLOBALS[$var] = $GLOBALS['member']['uid'];
        } else {
            $GLOBALS[$var] = $uc_db->result_first('SELECT uid FROM ' . UC_DBTABLEPRE . "members WHERE username='$username'");
        }
    }
    return $GLOBALS[$var];
}

function get_user_name_by_id($uid)
{
    global $_G, $uc_db;
    if (!$uid) return '';
    
    $var = "my_get_name_$uid";
    if(!isset($GLOBALS[$var])) {
        if($uid == $_G['member']['uid']) {
            $GLOBALS[$var] = $_G['member']['username'];
        } else {
            $GLOBALS[$var] = $uc_db->result_first('SELECT username FROM ' . UC_DBTABLEPRE . "members WHERE uid='$uid'");
        }
    }
    
    return $GLOBALS[$var];
}

function is_subscribed($tid, $idtype = 'tid')
{
    global $_G;
    
    if ($_G['uid']) {
        return (DB::result_first('SELECT * FROM '.DB::table('home_favorite')." WHERE uid='$_G[uid]' AND idtype='$idtype' AND id='$tid'")) ? true : false;
    }
    
    return false;
}

function set_fid_tid()
{
    global $_G;
    
    $pid = (isset($_G['gp_pid']) && $_G['gp_pid']) ? $_G['gp_pid'] : ((isset($_G['gp_repquote']) && $_G['gp_repquote']) ? $_G['gp_repquote'] : '');
    
    if($pid)
    {
        $query = DB::query("SELECT fid, tid FROM ".DB::table('forum_post')." WHERE pid='$pid'");
        $result = DB::fetch($query);
        $_G['gp_fid'] = $_GET['fid'] = $result['fid'];
        $_G['gp_tid'] = $_GET['tid'] = $result['tid'];
    }
}

function get_forum_icon_url($forum_id, $icon)
{
    global $_G;
    
    $logo_url = '';
    if (file_exists("./forum_icons/$forum_id.png"))
    {
        $logo_url = $boardurl."forum_icons/$forum_id.png";
    }
    else if (file_exists("./forum_icons/$forum_id.jpg"))
    {
        $logo_url = $boardurl."forum_icons/$forum_id.jpg";
    }
    else if (file_exists("./forum_icons/default.png"))
    {
        $logo_url = $boardurl."forum_icons/default.png";
    }
    else if ($icon)
    {
        $board_url = $_G['setting']['discuzurl'].'/';
        $url_parse = parse_url($board_url);
        $site_url = $url_parse['scheme'].'://'.$url_parse['host'].(isset($url_parse['port']) && $url_parse['port'] ? ":$url_parse[port]" : '');
        $parse = parse_url($icon);
        if(isset($parse['host'])) {
            $logo_url = $icon;
        } elseif (preg_match('/^\//', $icon)) {
            $logo_url = $site_url.$icon;
        } else {
            $logo_url = $board_url.$_G['setting']['attachurl'].'common/'.$icon;
        }
    }
    return $logo_url;
}

function process_img_to_code($msg)
{
    $smiley = array(
        'smile.gif' => ':)',
        'sad.gif' => ':(',
        'biggrin.gif' => ':D',
        'cry.gif' => ':\'(',
        'huffy.gif' => ':@',
        'shocked.gif' => ':o',
        'tongue.gif' => ':P',
        'shy.gif' => ':$',
        'titter.gif' => ';P',
        'sweat.gif' => ':L',
        'mad.gif' => ':Q',
        'lol.gif' => ':lol',
        'loveliness.gif' => ':loveliness:',
        'funk.gif' => ':funk:',
        'curse.gif' => ':curse:',
        'dizzy.gif' => ':dizzy:',
        'shutup.gif' => ':shutup:',
        'sleepy.gif' => ':sleepy:',
        'hug.gif' => ':hug:',
        'victory.gif' => ':victory:',
        'time.gif' => ':time:',
        'kiss.gif' => ':kiss:',
        'handshake.gif' => ':handshake',
        'call.gif' => ':call:',
        '01.gif' => '{:2_25:}',
        '02.gif' => '{:2_26:}',
        '03.gif' => '{:2_27:}',
        '04.gif' => '{:2_28:}',
        '05.gif' => '{:2_29:}',
        '06.gif' => '{:2_30:}',
        '07.gif' => '{:2_31:}',
        '08.gif' => '{:2_32:}',
        '09.gif' => '{:2_33:}',
        '10.gif' => '{:2_34:}',
        '11.gif' => '{:2_35:}',
        '12.gif' => '{:2_36:}',
        '13.gif' => '{:2_37:}',
        '14.gif' => '{:2_38:}',
        '15.gif' => '{:2_39:}',
        '16.gif' => '{:2_40:}',
        '01.gif' => '{:3_41:}',
        '02.gif' => '{:3_42:}',
        '03.gif' => '{:3_43:}',
        '04.gif' => '{:3_44:}',
        '05.gif' => '{:3_45:}',
        '06.gif' => '{:3_46:}',
        '07.gif' => '{:3_47:}',
        '08.gif' => '{:3_48:}',
        '09.gif' => '{:3_49:}',
        '10.gif' => '{:3_50:}',
        '11.gif' => '{:3_51:}',
        '12.gif' => '{:3_52:}',
        '13.gif' => '{:3_53:}',
        '14.gif' => '{:3_54:}',
        '15.gif' => '{:3_55:}',
        '16.gif' => '{:3_56:}',
        '17.gif' => '{:3_57:}',
        '18.gif' => '{:3_58:}',
        '19.gif' => '{:3_59:}',
        '20.gif' => '{:3_60:}',
        '21.gif' => '{:3_61:}',
        '22.gif' => '{:3_62:}',
        '23.gif' => '{:3_63:}',
        '24.gif' => '{:3_64:}',
    );
    return preg_replace('#<img [^>]*?src="[^"]+?/smiley/[^"]+?/(\w+\.gif)"[^>]*?/>#ies', "\$smiley['$1']", $msg);
}

function get_user_state_by_id($uid)
{
   if ($uid>0){
        $query = DB::query("SELECT status,adminid,groupid FROM ".DB::table('common_member')." WHERE uid='$uid'");
        $result = DB::fetch($query);
        $status = $result['status'];
        $adminid = $result['adminid'];
        $groupid = $result['groupid'];
        $userstate = 1;
        if ($status == 0){
            if ($adminid == -1){
                if ($groupid == 4){
                    $userstate = 2; 
                }elseif ($groupid == 5){
                    $userstate = 3;
                }
            }
        }elseif ($status == -1){
            $userstate = 4;
        }
        return $userstate;  
   }
}