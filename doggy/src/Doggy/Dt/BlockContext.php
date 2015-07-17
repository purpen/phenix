<?php
/**
 * A context  hold a block-tag.
 *
 */
class Doggy_Dt_BlockContext {
    public $dt_safe = array('name', 'depth', 'super');
    public $block, $index;
    private $context;
    
    /**
     * construct
     *
     * @param Doggy_Dt_Tag_Block $block block tag
     * @param Doggy_Dt_Context $context global template context
     * @param int $index 
     */
    public function __construct($block, $context, $index) {
        $this->block =& $block;
        $this->context = $context;
        $this->index = $index;
    }

    /**
     * associated block name
     *
     * @return void
     */
    public function name() {
        return $this->block->name;
    }
    
    /**
     * associated block depth
     *
     * @return int
     */
    public function depth() {
        return $this->index;
    }
    /**
     * Return parent block render result.
     *
     * @return string
     */
    public function super() {
        $stream = new Doggy_Dt_StreamWriter();
        $this->block->parent->render($this->context, $stream, $this->index+1);
        return $stream->close(); 
    }
    
    function __toString() {
        return "[BlockContext : {$this->block->name}, {$this->block->filename}]";
    }
}
?>