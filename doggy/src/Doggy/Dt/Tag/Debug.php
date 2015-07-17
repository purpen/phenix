<?php
class Doggy_Dt_Tag_Debug extends Doggy_Dt_Tag {
    private $argument;
    function __construct($argstring, $parser, $pos = 0) {
        $this->argument = $argstring;
    }
    
    function render($context, $stream) {
        if ($this->argument) {
            $object = $context->resolve(symbol($this->argument));
        } else {
            $object = $context->scopes[0];
        }
        $output = "<pre>". print_r($object, true). "</pre>";
        $stream->write($output);
    }
}
?>