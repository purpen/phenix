<?php
class Doggy_Dt_Tag_Include extends Doggy_Dt_Tag {
    private $nodelist;
    private $syntax = '/^["\'](.*?)["\']$/';
    private $parser;
    private $dyn_load = false;
    private $template_var = null;
    
    public function __construct($argstring, $parser, $position = 0) {
        $args = $parser->parse_args($argstring);
        if (doggy_dt_is_sym($args[0])) {
            $this->parser = $parser;
            $this->template_var = $args[0];
            $this->dyn_load = true;
        }
        else {
            if (!preg_match($this->syntax, $args[0])) 
                throw new Doggy_Dt_Exception_TemplateSyntaxError('Include syntax error:position:$position');
            $this->filename = stripcslashes(substr($args[0], 1, -1));
            $this->nodelist = $parser->runtime->load_sub_template($this->filename, $parser->options);
            $parser->storage['templates'] = array_merge(
                $this->nodelist->parser->storage['templates'], $parser->storage['templates']
            );
            $parser->storage['templates'][] = $this->filename;
        }
    }
    
    protected function _dyn_load_template($context,$stream) {
        $this->filename = $context->resolve($this->template_var);
        if (empty($this->filename)) {
            return;
        }
        $parser = $this->parser;
        $nodelist = $parser->runtime->load_sub_template($this->filename, $parser->options);
        $parser->storage['templates'] = array_merge(
            $nodelist->parser->storage['templates'], $parser->storage['templates']
        );
        $parser->storage['templates'][] = $this->filename;
        return $nodelist->render($context,$stream);
    }

    public function render($context, $stream) {
        if ($this->dyn_load) {
            $this->_dyn_load_template($context,$stream);
        }
        else {
            // Doggy_Log_Helper::debug('render:included:'.$this->filename);
            $this->nodelist->render($context, $stream);
        }
        
    }
}
?>