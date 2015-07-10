<?php
class Doggy_Dt_Tag_Extends extends Doggy_Dt_Tag {
    public $filename;
    public $position;
    public $nodelist;
    private $syntax = '/^["\'](.*?)["\']$/';
    
    function __construct($argstring, $parser, $position = 0) {
      if (!$parser->first)
            throw new Doggy_Dt_Exception_TemplateSyntaxError('extends must be first in file');

      if (!preg_match($this->syntax, $argstring))
            throw new Doggy_Dt_Exception_TemplateSyntaxError('filename must be quoted');

        $this->filename = stripcslashes(substr($argstring, 1, -1));

        # Parse the current template
        $parser->parse();

        # Parse parent template
        $this->nodelist = $parser->runtime->load_sub_template($this->filename, $parser->options);
        $parser->storage['templates'] = array_merge(
            $parser->storage['templates'], $this->nodelist->parser->storage['templates']
        );
        $parser->storage['templates'][] = $this->filename;
        
        if (!isset($this->nodelist->parser->storage['blocks']) || !isset($parser->storage['blocks']))
            return ;

        # Blocks of parent template
        $blocks =& $this->nodelist->parser->storage['blocks'];

        # Push child blocks on top of parent blocks
        foreach($parser->storage['blocks'] as $name => &$block) {
            if (isset($blocks[$name])) {
                $blocks[$name]->add_layer($block);
            }
        }
    }
    
    function render($context, $stream) {
        $this->nodelist->render($context, $stream);
    }
}
?>