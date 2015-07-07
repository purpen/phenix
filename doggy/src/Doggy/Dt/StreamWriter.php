<?php
/**
 * A simple stream buffer writer for output/render
 *
 */
class Doggy_Dt_StreamWriter {
    var $buffer = array();
    var $close;

    function __construct() {
        $this->close = false;
    }

    function write($data) {
        if ($this->close)
            throw new Doggy_Dt_Exception('tried to write to closed stream');
        $this->buffer[] = $data;
    }

    function close() {
        $this->close = true;
        return implode('', $this->buffer);
    }
}
?>