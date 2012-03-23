<?php

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}
define('UC_API', strtolower(($_SERVER['HTTPS'] == 'on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'))));
require_once libfile('function/discuzcode');

class tool{
    //获取a-z+0-9随机数
    public static function randomkeys($i){
      $str = "abcdefghijklmnopqrstuvwxyz0123456789";
      $finalStr = "";
      for($j=0;$j<$i;$j++)
      {
        $finalStr .= substr($str,rand(0,35),1);
      }
      return $finalStr;
    }
    //获取当前发布修改时间解析(例如一分钟前.....)
    public static function dgmtimedate($timestamp, $format = 'dt', $timeoffset = '9999', $uformat = ''){
    global $_G;
    $format == 'u' && !$_G['setting']['dateconvert'] && $format = 'dt';
    static $dformat, $tformat, $dtformat, $offset, $lang;
    if($dformat === null) {
        $dformat = getglobal('setting/dateformat');
        $tformat = getglobal('setting/timeformat');
        $dtformat = $dformat.' '.$tformat;
        $offset = getglobal('member/timeoffset');
        $lang = lang('core', 'date');
    }
    $timeoffset = $timeoffset == 9999 ? $offset : $timeoffset;
    $timestamp += $timeoffset * 3600;
    $format = empty($format) || $format == 'dt' ? $dtformat : ($format == 'd' ? $dformat : ($format == 't' ? $tformat : $format));
        if($format == 'u') {
            $todaytimestamp = TIMESTAMP - (TIMESTAMP + $timeoffset * 3600) % 86400 + $timeoffset * 3600;
            $s = gmdate(!$uformat ? str_replace(":i", ":i", $dtformat) : $uformat, $timestamp);
            $time = TIMESTAMP + $timeoffset * 3600 - $timestamp;
            if($timestamp >= $todaytimestamp) {
                if($time > 3600) {
                    return intval($time / 3600).''.$lang['hour'].$lang['before'];
                } elseif($time > 1800) {
                    return $lang['half'].$lang['hour'].$lang['before'].'';
                } elseif($time > 60) {
                    return intval($time / 60).''.$lang['min'].$lang['before'];
                } elseif($time > 0) {
                    return $time.''.$lang['sec'].$lang['before'];
                } elseif($time == 0) {
                    return $lang['now'];
                } else {
                    return $s;
                }
                } elseif(($days = intval(($todaytimestamp - $timestamp) / 86400)) >= 0 && $days < 7) {
                    if($days == 0) {
                        return $lang['yday'].''.gmdate($tformat, $timestamp);
                    } elseif($days == 1) {
                        return $lang['byday'].''.gmdate($tformat, $timestamp);
                    } else {
                        return ($days + 1).''.$lang['day'].$lang['before'];
                    }
                } else {
                    //return  date("Y-m-d H:i",$s);
                    return date("Y-m-d",strtotime($s));
                }
            } else {
            return gmdate($format, $timestamp);
        }
    }

    //解析message
    public static function deleMessage($message){
        $msglist=array();
        $qpmgs1 = count(explode('[quote]',$message));
        $spmgs1 = count(explode('[size=2]',$message));
        $cpmgs1 = count(explode('[color=#999999]',$message));
        if ($qpmgs1>=2&&$spmgs1>=2&&$cpmgs1>=2){
        preg_match_all('/\[color.*\](.*?)\[\/color\]/i', $message,  $colormsg);
        $cmsg = $colormsg[1];
        $pmsg= explode('[/size]',$message);
        $pmgs1 = explode('[/quote]',$pmsg[1]);
        $msg =tool::deletetime($cmsg[0].$pmgs1[0]);
        $message = tool::deletetime($pmgs1[1]);
        }
        //$message =discuzcode($message,"");//过滤
        preg_match_all('/\<(.*?)\\>/i', $message,  $deletemsg);
        $cmsg1 = $deletemsg[0];
        $countcmsg1=count($cmsg1); //[]出现的总数
        preg_match_all('/\[attach\](.*?)\\[\/attach\]/i', $message,  $deleteZmsg);
        $cmsgz=$deleteZmsg[0];
        $countcmsgZ=count($cmsgz); //[]出现的总数
        $message = tool::deletemessage($message,$countcmsgZ,$cmsgz);
        $message = tool::deletemessage($message,$countcmsg1,$cmsg1);
        $message= tool::deletetime($message);
        $msglist=array("message" => "$message");
        return $msglist;
    }
    //清除字符串
    public static function deletetime($str,$model=0){
        $str = trim($str);
        //$str = ereg_replace("\t","",$str);
        //$str = ereg_replace(" ","",$str);
        $str = ereg_replace("\r","",$str);
        $str = ereg_replace("   ","",$str);
        $str = ereg_replace("\"","'",$str);
        $str = ereg_replace("&nbsp;","",$str);
        if ($model=1){
          $str = ereg_replace("\r\n","",$str);
          $str = ereg_replace("\n","",$str);
        }
        return trim($str);
    }
    
  public static function delmsg($str,$model=0)
  {
      $str = trim($str);
      $str = ereg_replace("\r","",$str);
      $str = ereg_replace("   ","",$str);
      $str = ereg_replace("\"","'",$str);
      $str = ereg_replace("&nbsp;","",$str);
      if ($model=1){
        $str = ereg_replace("\r\n","",$str);
        $str = ereg_replace("\n","",$str);
      }                       
      $img_preg = "/<img src=[^>]+>/"; 
      $rtstr = preg_replace($img_preg,"",$str);
      return $str;
  }  
    
    function deletemessage($fmessage,$count,$msg){
    for ($fi=0;$fi<$count;$fi++){
                $strcmsg1 = $msg[$fi];
                $fmessage= str_replace($strcmsg1,"",$fmessage);
            }
        return $fmessage;
    }
    
    //字符串截取(内容,截取数、 从多少开始、 编码格式)
    function cut_str($string, $sublen, $start = 0, $code = 'UTF-8') 
    { 
        if($code == 'UTF-8') 
        { 
            $pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/"; 
            preg_match_all($pa, $string, $t_string); 
     
            if(count($t_string[0]) - $start > $sublen) return join('', array_slice($t_string[0], $start, $sublen))."..."; 
            return join('', array_slice($t_string[0], $start, $sublen)); 
        } 
        else 
        { 
            $start = $start*2; 
            $sublen = $sublen*2; 
            $strlen = strlen($string); 
            $tmpstr = ''; 
     
            for($i=0; $i< $strlen; $i++) 
            { 
                if($i>=$start && $i< ($start+$sublen)) 
                { 
                    if(ord(substr($string, $i, 1))>129) 
                    { 
                        $tmpstr.= substr($string, $i, 2); 
                    } 
                    else 
                    { 
                        $tmpstr.= substr($string, $i, 1); 
                    } 
                } 
                if(ord(substr($string, $i, 1))>129) $i++; 
            } 
            if(strlen($tmpstr)< $strlen ) $tmpstr.= "..."; 
            return $tmpstr; 
        } 
    }
    
    //对[....] ..数据进行处理
    public static function discuzcode($message,$model ,$smileyoff, $bbcodeoff, $htmlon = 0, $allowsmilies = 1, $allowbbcode = 1, $allowimgcode = 1, $allowhtml = 0, $jammer = 0, $parsetype = '0', $authorid = '0', $allowmediacode = '0', $pid = 0) {
    global $_G;

    static $authorreplyexist;

    if($parsetype != 1 && !$bbcodeoff && $allowbbcode && (strpos($message, '[/code]') || strpos($message, '[/CODE]')) !== FALSE) {
        $message = preg_replace("/\s?\[code\](.+?)\[\/code\]\s?/ies", "codedisp('\\1')", $message);
    }

    $msglower = strtolower($message);

    $htmlon = $htmlon && $allowhtml ? 1 : 0;

    if(!$htmlon) {
        $message = dhtmlspecialchars($message);
    }

    if(!$smileyoff && $allowsmilies) {
        $message = tool::parsesmiles($message,$model);
    }

    if($_G['setting']['allowattachurl'] && strpos($msglower, 'attach://') !== FALSE) {
        $message = preg_replace("/attach:\/\/(\d+)\.?(\w*)/ie", "parseattachurl('\\1', '\\2')", $message);
    }

    if($allowbbcode) {
        if(strpos($msglower, 'ed2k://') !== FALSE) {
            $message = preg_replace("/ed2k:\/\/(.+?)\//e", "parseed2k('\\1')", $message);
        }
    }

    if(!$bbcodeoff && $allowbbcode) {
        if(strpos($msglower, '[/url]') !== FALSE) {
            $message = preg_replace("/\[url(=((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|synacast){1}:\/\/|www\.|mailto:)?([^\s\[\"']+?))?\](.+?)\[\/url\]/ies", "parseurl('\\1', '\\5', '\\2')", $message);
        }
        if(strpos($msglower, '[/email]') !== FALSE) {
            $message = preg_replace("/\[email(=([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+))?\](.+?)\[\/email\]/ies", "parseemail('\\1', '\\4')", $message);
        }

        $nest = 0;
        while(strpos($msglower, '[table') !== FALSE && strpos($msglower, '[/table]') !== FALSE){
            $message = preg_replace("/\[table(?:=(\d{1,4}%?)(?:,([\(\)%,#\w ]+))?)?\]\s*(.+?)\s*\[\/table\]/ies", "parsetable('\\1', '\\2', '\\3')", $message);
            if(++$nest > 4) break;
        }

        $message = str_replace(array(
            '[/color]', '[/size]', '[/font]', '[/align]', '[b]', '[/b]', '[s]', '[/s]', '[hr]', '[/p]',
            '[i=s]', '[i]', '[/i]', '[u]', '[/u]', '[list]', '[list=1]', '[list=a]',
            '[list=A]', "\r\n[*]", '[*]', '[/list]', '[indent]', '[/indent]', '[/float]'
            ), array(
            '</font>', '</font>', '</font>', '</p>', '<strong>', '</strong>', '<strike>', '</strike>', '<hr class="l" />', '</p>', '<i class="pstatus">', '<i>',
            '</i>', '<u>', '</u>', '<ul>', '<ul type="1" class="litype_1">', '<ul type="a" class="litype_2">',
            '<ul type="A" class="litype_3">', '<li>', '<li>', '</ul>', '<blockquote>', '</blockquote>', '</span>'
            ), preg_replace(array(
            "/\[color=([#\w]+?)\]/i",
            "/\[color=(rgb\([\d\s,]+?\))\]/i",
            "/\[size=(\d{1,2}?)\]/i",
            "/\[size=(\d{1,2}(\.\d{1,2}+)?(px|pt)+?)\]/i",
            "/\[font=([^\[\<]+?)\]/i",
            "/\[align=(left|center|right)\]/i",
            "/\[p=(\d{1,2}|null), (\d{1,2}), (left|center|right)\]/i",
            "/\[float=(left|right)\]/i"

            ), array(
            "<font color=\"\\1\">",
            "<font style=\"color:\\1\">",
            "<font size=\"\\1\">",
            "<font style=\"font-size: \\1\">",
            "<font face=\"\\1 \">",
            "<p align=\"\\1\">",
            "<p style=\"line-height: \\1px; text-indent: \\2em; text-align: \\3;\">",
            "<span style=\"float: \\1;\">"
            ), $message));

        if($parsetype != 1) {
            if(strpos($msglower, '[/quote]') !== FALSE) {
                $message = preg_replace("/\s?\[quote\][\n\r]*(.+?)[\n\r]*\[\/quote\]\s?/is", tpl_quote(), $message);
            }
            if(strpos($msglower, '[/free]') !== FALSE) {
                $message = preg_replace("/\s*\[free\][\n\r]*(.+?)[\n\r]*\[\/free\]\s*/is", tpl_free(), $message);
            }
        }
        if(strpos($msglower, '[/media]') !== FALSE) {
            $message = preg_replace("/\[media=([\w,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/ies", $allowmediacode ? "parsemedia('\\1', '\\2')" : "bbcodeurl('\\2', '<a href=\"{url}\" target=\"_blank\">{url}</a>')", $message);
        }
        if(strpos($msglower, '[/audio]') !== FALSE) {
            $message = preg_replace("/\[audio(=1)*\]\s*([^\[\<\r\n]+?)\s*\[\/audio\]/ies", $allowmediacode ? "parseaudio('\\2', 400, '\\1')" : "bbcodeurl('\\2', '<a href=\"{url}\" target=\"_blank\">{url}</a>')", $message);
        }
        if(strpos($msglower, '[/flash]') !== FALSE) {
            $message = preg_replace("/\[flash(=(\d+),(\d+))?\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/ies", $allowmediacode ?
                "parseflash('\\2', '\\3', '\\4');" : "bbcodeurl('\\4', '<a href=\"{url}\" target=\"_blank\">{url}</a>')",
                $message);
        }
        if($parsetype != 1 && $allowbbcode < 0 && isset($_G['cache']['bbcodes'][-$allowbbcode])) {
            $message = preg_replace($_G['cache']['bbcodes'][-$allowbbcode]['searcharray'], $_G['cache']['bbcodes'][-$allowbbcode]['replacearray'], $message);
        }
        if($parsetype != 1 && strpos($msglower, '[/hide]') !== FALSE && $pid) {
            if(strpos($msglower, '[hide]') !== FALSE) {
                if($authorreplyexist === null) {
                    $posttable = getposttablebytid($_G['tid']);
                    $authorreplyexist = !$_G['forum']['ismoderator'] ? DB::result_first("SELECT pid FROM ".DB::table($posttable)." WHERE tid='$_G[tid]' AND ".($_G['uid'] ? "authorid='$_G[uid]'" : "authorid=0 AND useip='$_G[clientip]'")." LIMIT 1") : TRUE;
                }
                if($authorreplyexist) {
                    $message = preg_replace("/\[hide\]\s*(.+?)\s*\[\/hide\]/is", tpl_hide_reply(), $message);
                } else {
                    $message = preg_replace("/\[hide\](.+?)\[\/hide\]/is", tpl_hide_reply_hidden(), $message);
                    $message .= '<script type="text/javascript">replyreload += \',\' + '.$pid.';</script>';
                }
            }
            if(strpos($msglower, '[hide=') !== FALSE) {
                $message = preg_replace("/\[hide=(\d+)\]\s*(.+?)\s*\[\/hide\]/ies", "creditshide(\\1,'\\2', $pid)", $message);
            }
        }
    }

    if(!$bbcodeoff) {
        if($parsetype != 1 && strpos($msglower, '[swf]') !== FALSE) {
            $message = preg_replace("/\[swf\]\s*([^\[\<\r\n]+?)\s*\[\/swf\]/ies", "bbcodeurl('\\1', ' <img src=\"'.STATICURL.'image/filetype/flash.gif\" align=\"absmiddle\" alt=\"\" /> <a href=\"{url}\" target=\"_blank\">Flash: {url}</a> ')", $message);
        }
        if(strpos($msglower, '[/img]') !== FALSE) {
            $message = preg_replace(array(
                "/\[img\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/ies",
                "/\[img=(\d{1,4})[x|\,](\d{1,4})\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/ies"
            ), $allowimgcode ? array(
                "bbcodeurl('\\1', '<img src=\"{url}\" onload=\"thumbImg(this)\" alt=\"\" />')",
                "parseimg('\\1', '\\2', '\\3')"
            ) : array(
                "bbcodeurl('\\1', '<a href=\"{url}\" target=\"_blank\">{url}</a>')",
                "bbcodeurl('\\3', '<a href=\"{url}\" target=\"_blank\">{url}</a>')"
            ), $message);
        }
    }

    for($i = 0; $i <= $_G['forum_discuzcode']['pcodecount']; $i++) {
        $message = str_replace("[\tDISCUZ_CODE_$i\t]", $_G['forum_discuzcode']['codehtml'][$i], $message);
    }

    if(!empty($_G['gp_highlight'])) {
        $highlightarray = explode('+', $_G['gp_highlight']);
        $sppos = strrpos($message, chr(0).chr(0).chr(0));
        if($sppos !== FALSE) {
            $specialextra = substr($message, $sppos + 3);
            $message = substr($message, 0, $sppos);
        }
        $message = preg_replace(array("/(^|>)([^<]+)(?=<|$)/sUe", "/<highlight>(.*)<\/highlight>/siU"), array("highlight('\\2', \$highlightarray, '\\1')", "<strong><font color=\"#FF0000\">\\1</font></strong>"), $message);
        if($sppos !== FALSE) {
            $message = $message.chr(0).chr(0).chr(0).$specialextra;
        }
    }

    unset($msglower);

    if($jammer) {
        $message = preg_replace("/\r\n|\n|\r/e", "jammer()", $message);
    }

    return $htmlon ? $message : nl2br(str_replace(array("\t", '   ', '  '), array('&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;'), $message));
}
        
        
function tpl_quote() {
$return = <<<EOF
<div class="quote">\\1</div>
EOF;
 return $return; 
}
        
//修改解析[img]
function parsesmiles(&$message,$model) {
    //echo $model;
    global $_G;
    static $enablesmiles;
    if($enablesmiles === null) {
        $enablesmiles = false;
        if(!empty($_G['cache']['smilies']) && is_array($_G['cache']['smilies'])) {
            foreach($_G['cache']['smilies']['replacearray'] AS $key => $smiley) {
                if ($model=="0"){
                $_G['cache']['smilies']['replacearray'][$key] = '';
                }else{
                $_G['cache']['smilies']['replacearray'][$key] = '<img src="'.UC_API."/".STATICURL.'image/smiley/'.$_G['cache']['smileytypes'][$_G['cache']['smilies']['typearray'][$key]]['directory'].'/'.$smiley.'" smilieid="'.$key.'" border="0" alt="" />';
                }
            }
            $enablesmiles = true;
        }
    }
    $enablesmiles && $message = preg_replace($_G['cache']['smilies']['searcharray'], $_G['cache']['smilies']['replacearray'], $message, $_G['setting']['maxsmilies']);
    return $message;
}

function trimbiaoq(&$message) {
    //echo $model;
    global $_G;
    static $enablesmiles;
    if($enablesmiles === null) {
        $enablesmiles = false;
        if(!empty($_G['cache']['smilies']) && is_array($_G['cache']['smilies'])) {
            foreach($_G['cache']['smilies']['replacearray'] AS $key => $smiley) {
                $_G['cache']['smilies']['replacearray'][$key] = '';
            }
            $enablesmiles = true;
        }
    }
    $enablesmiles && $message = preg_replace($_G['cache']['smilies']['searcharray'], $_G['cache']['smilies']['replacearray'], $message, $_G['setting']['maxsmilies']);
    return $message;
}

function strip_ubb( $str )
{
    $str = preg_replace('/\[img\].*?\[\/img\]/', '', $str);
    $str = preg_replace('/\[attach\].*?\[\/attach\]/', '', $str);
    $regexp = '/\[[a-z][^\]]*\]|\[\/[a-z]+\]/i';
    $str = preg_replace( $regexp,'',$str );
    $str = str_replace ( '\\n','',$str );
    $str = str_replace ( '\\','',$str );
    return $str;
}
}

?>