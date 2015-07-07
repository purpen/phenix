<?php
/**
 * Template parser
 *
 * @package Doggy_Dt
 */
class Doggy_Dt_Parser {
    
    public $first;
    /**
     * intermedia data storage
     *
     * @var array
     */
    public $storage = array();
    /**
     * Doggy_Dt runtime instance
     *
     * @var Doggy_Dt
     */
    public $runtime;
    /**
     * Current parsed filename
     *
     * @var string
     */
    public $filename;
    
    /**
     * Construct
     *
     * @param string $source 
     * @param string $filename 
     * @param Doggy_Dt $runtime 
     * @param array $options 
     */
    function __construct($source, $filename, $runtime, $options) {
        $this->options = $options;
        //$this->source = $source;
        $this->runtime = $runtime;
        $this->filename = $filename;
        $this->first = true;
        
        $this->tokenizer = new Doggy_Dt_Tokenizer($options);
        $this->tokenstream = $this->tokenizer->tokenize($source);
        $this->storage = array(
          'blocks' => array(),
          'templates' => array(),
          'included' => array()
        );
    }
    
    /**
     * Parse template
     *
     */
    public function &parse() {
        $until = func_get_args();
        $nodelist = new Doggy_Dt_NodeList($this);
        while($token = $this->tokenstream->next()) { 
            switch($token->type) {
                case 'text' :
                    $node = $token->content;
                    break;
                case 'variable' :
                    $args = self::parse_args($token->content, $token->position);
                    $variable = array_shift($args);
                    $filters = $args;
                    $node = new Doggy_Dt_VariableNode($variable, $filters, $token->position);
                    break;
                case 'comment' :
                    $node = '';
                    break;
                case 'block' :
                    if (in_array($token->content, $until)) {
                        $this->token = $token;                      
                        return $nodelist;
                    }
                    @list($name, $args) = preg_split('/\s+/',$token->content, 2);
                    $node = Doggy_Dt::create_tag($name, $args, $this, $token->position);
                    $this->token = $token;
            }
            $this->searching = join(',',$until);
            $this->first = false;
            $nodelist->append($node);
        }

        if ($until) {
            throw new Doggy_Dt_Exception_TemplateSyntaxError('Unclose tag, expecting '. $until[0]);
        }
        return $nodelist;
    }

    public function raw_skip_to($until) {
        $until = func_get_args();
        while($token = $this->tokenstream->next()) { 
            switch($token->type) {
                case 'block' :
                    if (in_array($token->content, $until)) {
                        $this->token = $token;
                        return;
                    }
            }
            $this->searching = join(',',$until);
            $this->first = false;
        }
        if ($until) {
            throw new Doggy_Dt_Exception_TemplateSyntaxError('Unclose tag, expecting '. $until[0]);
        }
        return;
    }

    public function skip_to($until) {
        $this->parse($until);
        return null;
    }

    /**
     * Parse arguments,return parsed string-token array
     *
     * @param string $source 
     * @param int $fpos 
     * @return array
     */
    public static function parse_args($source = null, $fpos = 0){
        
        $parser = new Doggy_Dt_ArgumentTokenizer($source, $fpos);
        $result = array();
        $current_buffer = &$result;
        $filter_buffer = array();
        $tokens = $parser->parse();
        foreach ($tokens as $token) {
            list($token, $data) = $token;
            if ($token == 'filter_start') {
                $filter_buffer = array();
                $current_buffer = &$filter_buffer;
            }
            elseif ($token == 'filter_end') {
                if (count($filter_buffer))
                    $result[] = $filter_buffer;
                $current_buffer = &$result;
            }
            elseif ($token == 'boolean') {
                $current_buffer[] = ($data === 'true'? true : false);
            }            
            elseif ($token == 'name') {
                $current_buffer[] = doggy_dt_symbol($data);
            }
            elseif ($token == 'number' || $token == 'string') { 
                $current_buffer[] = $data;
            } 
            elseif ($token == 'named_argument') {
                // $last = isset($current_buffer[count($current_buffer) - 1])?$current_buffer[count($current_buffer)-1]:null;
                $last = count($current_buffer) ? $current_buffer[count($current_buffer)-1]:null;
                if (!is_array($last))
                    $current_buffer[] = array();

                $namedArgs =& $current_buffer[count($current_buffer) - 1]; 
                list($name,$value) = array_map('trim', explode(':', $data, 2));
                
                # if argument value is variable mark it
                $value = self::parse_args($value);
                $namedArgs[$name] = $value[0];
            }
            elseif( $token == 'operator') {
                $current_buffer[] = array('operator'=>$data);
            }
        }
        return $result;
    }
}
?>