<?php
class Doggy_Dt_Tag_MustacheTemplate extends Doggy_Dt_Tag {
    protected $tpl = '';
    public function __construct($argstring, $parser, $pos = 0) {
        $args = $parser->parse_args($argstring);
        $id = '';
        $tpl = '';
        extract($args[0],EXTR_IF_EXISTS);
        $tpl = substr($tpl, 1, -1);
        $id = substr($id, 1, -1);
        if (empty($id)) {
            throw new Doggy_Dt_Exception_TemplateSyntaxError("Mustache syntax error:missing id:$position");
        }
        if (empty($tpl)) {
            throw new Doggy_Dt_Exception_TemplateSyntaxError("Mustache syntax error:missing tpl:$position");
        }
        $content = $parser->runtime->loader->read($tpl,false);
        $this->tpl = "<script id='$id' type='text/template'>\n$content\n</script>";
    }
    public function render($context, $stream) {
        return $stream->write($this->tpl);
    }
}
?>