<?php
class Doggy_Dt_Filters_String implements Doggy_Dt_FilterLib {

    static function humanize($string) {
        $string = preg_replace('/\s+/', ' ', trim(preg_replace('/[^A-Za-z0-9()!,?$]+/', ' ', $string)));
        return self::capfirst($string);
    }
    
    static function capitalize($string) {
        return ucwords(strtolower($string)) ;
    }
    
    static function titlize($string) {
        return self::capitalize($string);
    }
    
    static function capfirst($string) {
        $string = strtolower($string);
        return strtoupper($string{0}). substr($string, 1, strlen($string));
    }
    
    static function tighten_space($value) {
        return preg_replace("/\s{2,}/", ' ', $value);
    }
    
    static function escape($value, $attribute = false) {
        $value = urldecode($value);
        return htmlspecialchars($value, $attribute ? ENT_QUOTES : ENT_NOQUOTES,'UTF-8',false);
    }
    
    static function force_escape($value, $attribute = false) {
        return self::escape($value, $attribute);
    }
    
    static function e($value, $attribute = false) {
        return self::escape($value, $attribute);
    }
    
    static function safe($value) {
        return $value;
    }
    
    static function truncate ($string, $max = 50, $ends = '...') {
        mb_internal_encoding("UTF-8");
		//return (mb_strlen($string) > $max ? mb_substr($string, 0, $max).$ends : $string);
        return (mb_strwidth($string, 'utf8') > $max ? mb_strimwidth($string, 0, $max, $ends, 'utf8') : $string);
    }    
    
    static function RemoveXSS($value) {    
	   // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed    
	   // this prevents some character re-spacing such as <java\0script>    
	   // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs    
	   $val = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $value);

	   // straight replacements, the user should never need these since they're normal characters    
	   // this prevents like <IMG SRC=@avascript:alert('XSS')>    
	   $search = 'abcdefghijklmnopqrstuvwxyz';   
	   $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';    
	   $search .= '1234567890!@#$%^&*()';   
	   $search .= '~`";:?+/={}[]-_|\'\\';   
	   for ($i = 0; $i < strlen($search); $i++) {   
	      // ;? matches the ;, which is optional   
	      // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars   

	      // @ @ search for the hex values   
	      $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;   
	      // @ @ 0{0,7} matches '0' zero to seven times    
	      $val = preg_replace('/(�{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;   
	   }   

	   // now the only remaining whitespace attacks are \t, \n, and \r   
	   $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');   
	   $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');   
	   $ra = array_merge($ra1, $ra2);   

	   $found = true; // keep replacing as long as the previous round replaced something   
	   while ($found == true) {   
	      $val_before = $val;   
	      for ($i = 0; $i < sizeof($ra); $i++) {   
	         $pattern = '/';   
	         for ($j = 0; $j < strlen($ra[$i]); $j++) {   
	            if ($j > 0) {   
	               $pattern .= '(';    
	               $pattern .= '(&#[xX]0{0,8}([9ab]);)';   
	               $pattern .= '|';    
	               $pattern .= '|(�{0,8}([9|10|13]);)';   
	               $pattern .= ')*';   
	            }   
	            $pattern .= $ra[$i][$j];   
	         }   
	         $pattern .= '/i';    
	         $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag    
	         $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags    
	         if ($val_before == $val) {    
	            // no replacements were made, so exit the loop    
	            $found = false;    
	         }    
	      }    
	   }    
	   return $val;    
	}
    
}