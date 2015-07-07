<?php
class Doggy_Dt_Tag_For extends Doggy_Dt_Tag {
    public $position;
    private $iteratable, $key, $item, $body, $else, $limit, $reversed;
    private $syntax = '{
        ([a-zA-Z][a-zA-Z0-9-_]*)(?:,\s?([a-zA-Z][a-zA-Z0-9-_]*))?
        \s+in\s+
        ([a-zA-Z][a-zA-Z0-9-_]*(?:\.[a-zA-Z_0-9][a-zA-Z0-9_-]*)*)\s*   # Iteratable name
        (?:limit\s*:\s*(\d+))?\s*
        (reversed)?                                                     # Reverse keyword
    }x';

    function __construct($argstring, $parser, $position) {
        if (!preg_match($this->syntax, $argstring, $match))
            throw new Doggy_Dt_Exception_TemplateSyntaxError("Invalid for loop syntax ");
        
        $this->body = $parser->parse('endfor', 'else');
        
        if ($parser->token->content === 'else')
            $this->else = $parser->parse('endfor');

        @list(,$this->key, $this->item, $this->iteratable, $this->limit, $this->reversed) = $match;
        
        if ($this->limit)
            $this->limit = (int) $this->limit;

        # Swap value if no key found
        if (!$this->item) {
            list($this->key, $this->item) = array($this->item, $this->key);
        }
        $this->iteratable = doggy_dt_symbol($this->iteratable);
        $this->reversed = (bool) $this->reversed;
    }

    function render($context, $stream) {
        $iteratable = $context->resolve($this->iteratable);

        if ($this->reversed)
            $iteratable = array_reverse($iteratable);

        if ($this->limit)
            $iteratable = array_slice($iteratable, 0, $this->limit);

        $length = count($iteratable);
        
        if ($length) {
            $parent = $context['loop'];
            $context->push();
            $rev_count = $is_even = $idx = 0;
            foreach($iteratable as $key => $value) {
                $is_even =  $idx % 2;
                $rev_count = $length - $idx;
                
                if ($this->key) {
                    $context[$this->key] = $key;
                }
                $context[$this->item] =  $value;
                $context['loop'] = array(
                    'parent' => $parent,
                    'first' => $idx === 0, 
                    'last'  => $rev_count === 1,
                    'odd'   => !$is_even,
                    'even'  => $is_even,
                    'length' => $length,
                    'counter' => $idx + 1,
                    'counter0' => $idx,
                    'revcounter' => $rev_count,
                    'revcounter0' => $rev_count - 1
                );
                $this->body->render($context, $stream);
                ++$idx;                
            }
            $context->pop();
        } elseif ($this->else)
            $this->else->render($context, $stream);
    }
}
?>
