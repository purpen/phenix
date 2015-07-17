<?php
class Doggy_Dt_Tag_Autoescape extends Doggy_Dt_Tag {
    protected $enable=false;
    
    public function __construct($argstring, $parser, $pos = 0) {
        if ($argstring === 'on') {
            $this->enable = true;
        }   
        elseif ($argstring === 'off') {
            $this->enable = false;
        }
        else {
            throw new Doggy_Dt_Exception_TemplateSyntaxError("Invalid syntax : autoescape on|off");
        }
    }
    
    public function render($context, $stream) {
        $context->autoescape = $this->enable;
    }
}
?>