<?php
class Doggy_Dt_Filters_Html implements Doggy_Dt_FilterLib {
    static function base_url($url, $options = array()) {
        $root = Doggy_Config::get('app.url.base');
        return empty($root)?$url:$root.$url;
    }
    
    static function asset_url($url, $options = array()) {
        return self::base_url($url, $options);
    }
    
    static function image_tag($url, $options = array()) {
        $attr = self::html_attribute(array('alt','width','height','border'), $options);
        return sprintf('<img src="%s" %s/>', $url, $attr);
    }

    static function css_tag($url, $options = array()) {
        $attr = self::html_attribute(array('media'), $options);
        return sprintf('<link rel="stylesheet" href="%s" type="text/css" %s />', $url, $attr);
    }

    static function script_tag($url, $options = array()) {
        return sprintf('<script src="%s" type="text/javascript"></script>', $url);
    }
    
    static function links_to($text, $url, $options = array()) {
        $attrs = self::html_attribute(array('ref','target'), $options);
        $url = self::base_url($url, $options);
        return sprintf('<a href="%s" %s>%s</a>', $url, $attrs, $text);
    }
    
    static function links_with ($url, $text, $options = array()) {
        return self::links_to($text, $url, $options);
    }
    
    static function strip_tags($text) {
        $text = preg_replace(array('/</', '/>/'), array(' <', '> '),$text);
        return trim(strip_tags($text));
    }

    static function linebreaks($value, $format = 'p') {
        if ($format === 'br')
            return self::nl2br($value);
        return self::nl2pbr($value);
    }
    
    static function nl2br($value) {
        return str_replace("\n", "<br />\n", $value);
    }
    
    static function nl2pbr($value) {
        $result = array();
        $parts = preg_split('/(\r?\n){2,}/m', $value);
        foreach ($parts as $part) {
            array_push($result, '<p>' . self::nl2br($part) . '</p>');
        }
        return implode("\n", $result);
    }

    protected static function html_attribute($attrs = array(), $data = array()) {
        $attrs = self::extract(array_merge(array('id', 'class', 'title', "style"), $attrs), $data);
        
        $result = array();
        foreach ($attrs as $name => $value) {
            $result[] = "{$name}=\"{$value}\"";
        }
        return join(' ', $result);
    }

    protected static function extract($attrs = array(), $data=array()) {
        $result = array();
        if (empty($data)) return array();
        foreach($data as $k => $e) {
            if (in_array($k, $attrs)) $result[$k] = $e;
        }
        return $result;
    }
}