<?php
class Doggy_Dt_Tag_Load extends Doggy_Dt_Tag {
    public $position;
    private $extension;

    function __construct($argstring, $parser, $pos = 0) {
        $this->extension = stripcslashes(substr($argstring, 1, -1));
        if ($this->load()) {
            $parser->storage['extension'] = $this->extension;
        }
        $this->position = $pos;
    }

    function render($context, $stream) {
        $this->load();
    }

    private function load() {
        if (isset(Doggy_Dt::$extensions[$this->extension])) {
            return true;
        }
        if(!Doggy_Dt::load($this->extension)) {
            throw new Doggy_Dt_Exception(
                "Extension: {$this->extension} cannot be loaded, please confirm it defined in app.dt.extension_lib."
            );
        }
    }
}
?>