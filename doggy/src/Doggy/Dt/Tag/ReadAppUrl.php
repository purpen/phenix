<?php
class Doggy_Dt_Tag_ReadAppUrl extends Doggy_Dt_Tag {
    private $url_keys = array(
        'register','login','logout','deny','action','base','css','images','js',
        );
    public function __construct($argstring, $parser, $position = 0) {
        if (!empty($argstring)) {
            $args = Doggy_Dt_Parser::parse_args($argstring);
            $keys = array();
            foreach ($args as $key) {
                $this->url_keys[] = doggy_dt_is_sym($key)?doggy_dt_sym_to_str($key):trim($key,'\'"');
            }
            $this->url_keys = array_unique($this->url_keys);
        }
    }
    public function render($context,$stream) {
        $app_urls = array();
        foreach ($this->url_keys as $url) {
            $key = strtolower($url);
            $context->set('app_url_'.$key, Doggy_Config::get('app.url.'.$key));
        }
        
    }
}
?>