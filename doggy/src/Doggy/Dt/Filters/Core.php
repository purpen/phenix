<?php
class Doggy_Dt_Filters_Core implements Doggy_Dt_FilterLib {
    static function first($value) {
        return $value[0];
    }
    
    static function last($value) {
        return $value[count($value) - 1];
    }
    
    static function join($value, $delimiter = ', ') {
        return join($delimiter, $value);
    }
    
    static function urlencode($data) {
        if (is_array($data)) {
            $result='';
            foreach ($data as $name => $value) {
                $result .= $name.'='.urlencode($value).'&';
            }
            $result = substr($result, 0, strlen($result)-1);
            return htmlspecialchars($result);
        } else {
            return urlencode($data);
        }
    }
    
    static function hyphenize ($string) {
        $rules = array('/[^\w\s-]+/'=>'','/\s+/'=>'-', '/-{2,}/'=>'-');
        $string = preg_replace(array_keys($rules), $rules, trim($string));
        return $string = trim(strtolower($string));
    }
 
    static function urlize($url, $truncate = false) {
        if (preg_match('/^(http|https|ftp:\/\/([^\s"\']+))/i', $url, $match))
            $url = "<a href='{$url}'>". ($truncate ? truncate($url,$truncate): $url).'</a>';
        return $url;
    }

    static function set_default($object, $default) {
        return !$object ? $default : $object;
    }
}