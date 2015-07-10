<?php
/**
 * 英文单词形式转换类
 *
 * @package Doggy
 * @subpackage Util
 */
class Doggy_Util_Inflector {
    /**
     * 将单数英文单词转换为复数形式
     *
     * @param string $word
     * @return string
     */
    public static function pluralize($word) {
        $plural_rules =
        array(  '/(x|ch|ss|sh)$/' => '\1es',            # search, switch, fix, box, process, address
                '/series$/' => '\1series',
                '/([^aeiouy]|qu)ies$/' => '\1y',
                '/([^aeiouy]|qu)y$/' => '\1ies',        # query, ability, agency
                '/(?:([^f])fe|([lr])f)$/' => '\1\2ves', # half, safe, wife
                '/sis$/' => 'ses',                      # basis, diagnosis
                '/([ti])um$/' => '\1a',                 # datum, medium
                '/person$/' => 'people',                # person, salesperson
                '/man$/' => 'men',                      # man, woman, spokesman
                '/child$/' => 'children',               # child
                '/(.+)status$/' => '\1statuses',
                '/s$/' => 's',                          # no change (compatibility)
                '/$/' => 's'
        );
        $original = $word;
        foreach( $plural_rules as $rule => $replacement) {
            $word = preg_replace($rule,$replacement,$word);
            if($original != $word) break;
        }
        return $word;
    }

    /**
     * 返回英文单词的单数形式
     *
     * @param string $word
     * @return string
     */
    public static function singularize($word) {
        $singular_rules =
        array(  '/(x|ch|ss)es$/' => '\1',
                '/movies$/' => 'movie',
                '/series$/' => 'series',
                '/([^aeiouy]|qu)ies$/' => '\1y',
                '/([lr])ves$/' => '\1f',
                '/([^f])ves$/' => '\1fe',
                '/(analy|ba|diagno|parenthe|progno|synop|the)ses$/' => '\1sis',
                '/([ti])a$/' => '\1um',
                '/people$/' => 'person',
                '/men$/' => 'man',
                '/(.+)status$/' => '\1status',
                '/children$/' => 'child',
                '/news$/' => 'news',
                '/s$/' => ''
        );
        $original = $word;
        foreach($singular_rules as $rule => $replacement) {
            $word = preg_replace($rule,$replacement,$word);
            if($original != $word) break;
        }
        return $word;
    }

    /**
     * 将英文单词转换为camel case形式(LikeThis)
     *
     * @param string $lower_case_and_underscored_word
     * @return string
     */
    public static function camelize($lower_case_and_underscored_word) {
        return str_replace(" ","",ucwords(str_replace("_"," ",$lower_case_and_underscored_word)));
    }
    /**
     * 将camel case形式单词转换为下划线的单词
     *
     * @param string $camel_cased_word
     * @return string
     */
    public static function underscore($camel_cased_word) {
        $camel_cased_word = preg_replace('/([A-Z]+)([A-Z])/','\1_\2',$camel_cased_word);
        return strtolower(preg_replace('/([a-z])([A-Z])/','\1_\2',$camel_cased_word));
    }
    /**
     * 将小写下划线单词转换为词组
     *
     * @param string $lower_case_and_underscored_word
     * @return string
     */
    public static function humanize($lower_case_and_underscored_word) {
        return ucwords(str_replace("_"," ",$lower_case_and_underscored_word));
    }
     /**
      * 转换为属性名(Camelize形式)
      *
      * @param mixed $data
      * @return mixed
      */
     public static function attributelize($data){
         if(is_array($data)){
             $result;
             foreach ($data as $k=>$v){
                 $result[$k] = self::camelize($v);
             }
             return $result;
         }else{
             return self::camelize($data);
         }

     }
     /**
      * Convert table name to a class name
      *
      * @param string $name
      * @return string
      */
     public static function classify($name){
         return self::camelize(self::singularize($name));
     }
     public static function lcFirst($words){
         $words[0] = strtolower($words[0]);
         return $words;
     }
     /**
      * convert to Java style camlize method,like:
      * get_method
      * to
      * getMethod
      *
      * @param unknown_type $method
      * @return unknown
      */
     public static function methodlize($method){
         $method = self::camelize($method);
         $method[0]=strtolower($method[0]);
         return $method;
     }
     public static function doggyClassify($class){
         $ok=str_replace(" ","_",ucwords(str_replace("_"," ",$class)));
         return $ok;
     }
}
?>
