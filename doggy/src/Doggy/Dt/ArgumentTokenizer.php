<?php
/**
 * Arguments lexer.
 *
 * This internal class is used by Doggy_Dt_Parser
 */
class Doggy_Dt_ArgumentTokenizer {
    private $source;
    private $match;
    private $pos = 0, $fpos, $eos;
    private $operator_map = array(
        '!' => 'not', '!='=> 'ne', '==' => 'eq', '>' => 'gt', '<' => 'lt', '<=' => 'le', '>=' => 'ge'
    );
    /**
     * construct
     *
     * @param string $source 
     * @param int $fpos 
     */
    public function __construct($source, $fpos = 0){
        if (!is_null($source)) {
            $this->source = $source;
        }
        $this->fpos=$fpos;
    }
    
    /**
     * parse and return array of parsed word token.
     *
     * @return array
     */
    public function parse(){
        $result = array();
        $filtering = false;
        while (!$this->eos()) {
            $this->scan(Doggy_Dt_Regex::$whitespace);
            if (!$filtering) {
                if ($this->scan(Doggy_Dt_Regex::$operator)){
                    $operator = trim($this->match);
                    if(isset($this->operator_map[$operator]))
                        $operator = $this->operator_map[$operator];
                    $result[] = array('operator', $operator);
                }
                elseif ($this->scan(Doggy_Dt_Regex::$boolean))
                    $result[] = array('boolean', $this->match);
                elseif ($this->scan(Doggy_Dt_Regex::$named_args))
                    $result[] = array('named_argument', $this->match);                      
                elseif ($this->scan(Doggy_Dt_Regex::$name))
                    $result[] = array('name', $this->match);
                elseif ($this->scan(Doggy_Dt_Regex::$pipe)) {
                    $filtering = true;
                    $result[] = array('filter_start', $this->match);
                }
                elseif ($this->scan(Doggy_Dt_Regex::$seperator))
                    $result[] = array('separator', null);
                elseif ($this->scan(Doggy_Dt_Regex::$i18n_string))
                    $result[] = array('string', $this->match);
                elseif ($this->scan(Doggy_Dt_Regex::$number))
                    $result[] = array('number', $this->match);
                else
                    throw new Doggy_Dt_Exception_TemplateSyntaxError('unexpected character in filters : "'. $this->source[$this->pos]. '" at '.$this->get_position());
            } 
            else {
                // parse filters, with chaining and ";" as filter end character
                if ($this->scan(Doggy_Dt_Regex::$pipe)) {
                    $result[] = array('filter_end', null);
                    $result[] = array('filter_start', null);
                }
                elseif ($this->scan(Doggy_Dt_Regex::$seperator))
                    $result[] = array('separator', null);
                elseif ($this->scan(Doggy_Dt_Regex::$filter_end)) {
                    $result[] = array('filter_end', null);
                    $filtering = false;
                }
                elseif ($this->scan(Doggy_Dt_Regex::$boolean))
                    $result[] = array('boolean', $this->match);
                elseif ($this->scan(Doggy_Dt_Regex::$named_args))
                    $result[] = array('named_argument', $this->match);
                elseif ($this->scan(Doggy_Dt_Regex::$name))
                    $result[] = array('name', $this->match);
                elseif ($this->scan(Doggy_Dt_Regex::$i18n_string))
                    $result[] = array('string', $this->match);
                elseif ($this->scan(Doggy_Dt_Regex::$number))
                    $result[] = array('number', $this->match);          
                else
                    throw new Doggy_Dt_Exception_TemplateSyntaxError('unexpected character in filters : "'. $this->source[$this->pos]. '" at '.$this->get_position());
            }
        }
        // if we are still in the filter state, we add a filter_end token.
        if ($filtering)
            $result[] = array('filter_end', null);
        return $result;
    }

    # String scanner
    private function scan($regexp) {
        
        if (preg_match($regexp . 'A', $this->source, $match, null, $this->pos)) {
            $this->match = $match[0];
            $this->pos += strlen($this->match);
            return true;
        }
        return false;
    }
    
    /**
     * is end of stream
     *
     * @return bool
     */
    public function eos() {
        return $this->pos >= strlen($this->source);
    }
    
    /**
     * return the position in the template
     * 
     * @return int
     */
    public function get_position() {
        return $this->fpos + $this->pos;
    }
}
?>