<?php
class Doggy_Dt_Tag_With extends Doggy_Dt_Tag {
    public $position;
    private $variable, $shortcut;
    private $nodelist;
    // private $syntax = '/^([\w]+(:?\.[\w]+)?)\s+as\s+([\w]+(:?\.[\w]+)?)$/';
    private $syntax = '/^([\w\._]+)\s+as\s+([\w_]+)$/';
    
    function __construct($argstring, $parser, $position = 0) {
        
        if (!preg_match($this->syntax, $argstring, $matches))
            throw new Doggy_Dt_Exception_TemplateSyntaxError("Invalid with tag syntax:$argstring");
            
        # extract the long name and shortcut
        $this->variable = $matches[1];
        $this->shortcut = $matches[2];
        $this->nodelist = $parser->parse('endwith');
    }
    
    function render($context, $stream) {
        $variable = $context->get_var($this->variable);
        $context->push(array($this->shortcut => $variable));
        $this->nodelist->render($context, $stream);
        $context->pop();
    }
}
?>