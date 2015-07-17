<?php
/**
 * Format like function, but first lookup app.config key as patten
 */
class Doggy_Dt_Tag_Format extends Doggy_Dt_Tag {
    protected $args;
    public function __construct($argstring, $parser, $pos = 0) {
        $this->args = $parser->parse_args($argstring);
    }

    public function render($context, $stream) {
        if (empty($this->args)) {
            return;
        }
        $args = $this->args;
        $key = $context->resolve(array_shift($args));
        $patten = isset(Doggy_Config::$vars[$key])?Doggy_Config::$vars[$key]:$key;
        
        $s_args[] = $patten;
        while ($arg = array_shift($args)) {
            $s_args[] = $context->resolve($arg);
        }
        $stream->write(call_user_func_array('sprintf',$s_args));
    }
}
?>