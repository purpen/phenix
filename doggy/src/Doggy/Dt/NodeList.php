<?php
class Doggy_Dt_NodeList extends Doggy_Dt_Node implements IteratorAggregate  {
	/**
	 * Current template parser
	 *
	 * @var Doggy_Dt_Parser
	 */
	public $parser;
	
	/**
	 * internal node array
	 *
	 * @var array
	 */
	public $list;
	
	public $position;
	
	/**
	 * Construct
	 *
	 * @param Doggy_Dt_Parser $parser 
	 * @param array $initial 
	 * @param int $position 
	 */
	function __construct(&$parser, $initial = null, $position = 0) {
	    $this->parser = $parser;
        if (is_null($initial))
            $initial = array();
        $this->list = $initial;
        $this->position = $position;
	}

    /**
     * Render nodelist on given context
     *
     * @param Doggy_Dt_Context $context 
     * @param Doggy_Dt_StreamWriter $stream 
     * @return void
     */
	function render($context, $stream) {
        // $i = 1;
		foreach($this->list as $node) {
            // Doggy_Log_Helper::debug("render:node >>$i:".get_class($node));
            // $i++;
            is_string($node)? $stream->write($node):$node->render($context, $stream);
		}
	}
	/**
	 * append a node
	 *
	 * @param Doggy_Dt_Node $node 
	 * @return void
	 */
    function append($node) {
        array_push($this->list, $node);
    }
    
    /**
     * merge given nodelist
     *
     * @param array $nodes 
     * @return void
     */
    function extend($nodes) {
        array_merge($this->list, $nodes);
    }

    /**
     * Returns nodelist size
     *
     * @return int
     */
    function getLength() {
        return count($this->list);
    }
    /**
     * Returns ArrayIterator
     *
     * @return ArrayIterator
     */
    function getIterator() {
        return new ArrayIterator( $this->list );
    }
        
    public function __sleep() {
        return array('list','position');
    }
}
?>