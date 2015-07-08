<?php
/**
 * A variable node.
 */
class Doggy_Dt_VariableNode extends Doggy_Dt_Node {
    private $filters = array();
    public $variable;
    /**
     * construct
     *
     * @param string $variable 
     * @param array $filters 
     * @param int $position 
     */
	function __construct($variable, $filters, $position = 0) {
        if (!empty($filters))
            $this->filters = $filters;
		$this->variable = $variable;
	}

	function render($context, $stream) {
        $value = $context->resolve($this->variable);
        $value = $context->apply_filters($value, $this->filters);
        // Doggy_Log_Helper::debug('render var['.$this->variable.']');
		$stream->write($value);
	}
}
?>