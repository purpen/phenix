<?php
class Doggy_Dt_Tag_Now extends Doggy_Dt_Tag {
    function __construct($argstring, $parser, $pos=0) {
        $this->format = $argstring;
        if (!$this->format) {
            $this->format = "D M j G:i:s T Y";
        }
    }
    
    function render($contxt, $stream) {
        //sleep(1);
        $time = date($this->format);
        $stream->write($time);
    }
}
?>