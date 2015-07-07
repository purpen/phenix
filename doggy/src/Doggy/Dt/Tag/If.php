<?php
class Doggy_Dt_Tag_If extends Doggy_Dt_Tag {
    private $body;
    private $else;
    private $negate;
    
    function __construct($argstring, $parser, $position = 0) {
        
        // if (preg_match('/\s(and|or)\s/', $argstring)) {
        //     throw new Doggy_Dt_Exception_TemplateSyntaxError('Doggy_DTemplate doesn\'t support multiple expressiosn');
        // }

        $this->body = $parser->parse('endif', 'else');
        
        if ($parser->token->content === 'else') {
            $this->else = $parser->parse('endif');
        }

        $this->args = Doggy_Dt_Parser::parse_args($argstring);

        $first = current($this->args);
        if (isset($first['operator']) && $first['operator'] === 'not') {
            array_shift($this->args);
            $this->negate = true;
        }
    }

    function render($context, $stream) {
        if ($this->test($context)) {
            $this->body->render($context, $stream);
        }
        elseif ($this->else) {
            $this->else->render($context, $stream);
        }
    }

    function test($context) {
        $test = Doggy_Dt_Evaluator::exec($this->args, $context);
        return $this->negate? !$test : $test;
    }
}
?>