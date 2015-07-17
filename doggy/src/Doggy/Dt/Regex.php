<?php
/**
 * Regex used in Doggy DTemplate
 */
class Doggy_Dt_Regex {
    public static $whitespace, $seperator, $parentheses, $pipe, $filter_end, $operator, $boolean, $number,  $string, $i18n_string, $name, $named_args;
    public static function init() {
        $r = 'doggy_dt_strip_regex';
        self::$whitespace   = '/\s+/m';
        self::$parentheses  = '/\(|\)/m';
        self::$filter_end   = '/;/';
        self::$boolean    = '/true|false/';
        self::$seperator    = '/,/';
        self::$pipe         = '/\|/';
        self::$operator     = '/\s?(>=|<=|>|<|!=|==|!|and |not |or )\s?/i';
        self::$number       = '/\d+(\.\d*)?/';
        self::$name         = '/[a-zA-Z][a-zA-Z0-9-_]*(?:\.[a-zA-Z_0-9][a-zA-Z0-9_-]*)*/';
        
        self::$string       = '/(?:
                "([^"\\\\]*(?:\\\\.[^"\\\\]*)*)" |   # Double Quote string   
                \'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\' # Single Quote String
        )/xsm';
        self::$i18n_string  = "/_\({$r(self::$string)}\) | {$r(self::$string)}/xsm";

        self::$named_args   = "{
            ({$r(self::$name)})(?:{$r(self::$whitespace)})?
            : 
            (?:{$r(self::$whitespace)})?({$r(self::$i18n_string)}|{$r(self::$number)}|{$r(self::$name)})
        }x";
    }
}
Doggy_Dt_Regex::init();
?>