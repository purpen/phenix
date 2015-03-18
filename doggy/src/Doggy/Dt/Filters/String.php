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
		return (mb_strlen($string) > $max ? mb_substr($string, 0, $max).$ends : $string);
    }    
}
?>