<?php
class Doggy_Dt_Tag_Cycle extends Doggy_Dt_Tag {
    private $uid;
    private $sequence;
    
    function __construct($argstring, $parser, $pos) {
        $args = Doggy_Dt_Parser::parse_args($argstring);
        
        if (count($args) < 2) {
            throw new Doggy_Dt_Exception_TemplateSyntaxError('Cycle tag require more than two items');
        }
        $this->sequence = $args;        
        $this->uid = '__cycle__'.$pos;
    }
    
    function render($context, $stream) {
        if (!is_null($item = $context->get_var($this->uid))) {
            $item = ($item + 1) % count($this->sequence);
        } else {
            $item = 0;
        }
        $stream->write($context->resolve($this->sequence[$item]));
        $context->set($this->uid, $item);
    }
}
?>