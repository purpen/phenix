<?php
class Doggy_Dt_Tag_Block extends Doggy_Dt_Tag {
    public $name;
    public $position;
    public $stack;
    private static $syntax = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    
    function __construct($argstring, $parser, $position) {
        if (!preg_match(self::$syntax, $argstring)) {
            throw new Doggy_Dt_Exception_TemplateSyntaxError('Block tag expects a name, example: block [content]');
        }

        $this->name = $argstring;

        if (isset($parser->storage['blocks'][$this->name])) {
            throw new Doggy_Dt_Exception_TemplateSyntaxError('Block name exists, Please select a different block name');
        }
        
        $this->filename = $parser->filename;
        $this->stack = array($parser->parse('endblock', "endblock {$this->name}"));

        $parser->storage['blocks'][$this->name] = $this;
        $this->position = $position;
    }

    function add_layer(&$nodelist) {
        $nodelist->parent = $this;
        array_push($this->stack, $nodelist);
    }

    function render($context, $stream, $index = 1) {
        $key = count($this->stack) - $index;

        if (isset($this->stack[$key])) {
            $context->push();
            $context['block'] = new Doggy_Dt_BlockContext($this, $context, $index);
            $this->stack[$key]->render($context, $stream);
            $context->pop();
        }
    }
}
?>