<?php
/**
 * A class represent a parsed token.
 */
class Doggy_Dt_Token {
    public $type;
    public $content;
    public $result;
    /**
     * token position in template
     *
     * @var int
     */
    public $position;
    
    public function __construct ($type, $content, $position) {
        $this->type = $type;
        $this->content = $content;
        $this->result='';
        $this->position = $position;
    }

    public function write($content){
        $this->result= $content;
    }
}
?>