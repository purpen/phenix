<?php
/**
 * Class represent a common/base node.
 *
 */
class Doggy_Dt_Node {
    /**
     * Node position in template.
     *
     * @var int
     */
    public  $position;
	public function __construct($argstring) {}
	/**
	 * Render current node content to the stream.
	 *
	 * @param Doggy_Dt_Context $context 
	 * @param Doggy_Dt_StreamWriter $stream 
	 * @return void
	 */
	public function render($context, $stream) {}
}
?>