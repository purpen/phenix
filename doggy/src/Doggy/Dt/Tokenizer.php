<?php
/**
 * A simple template lexer.
 *
 */
class Doggy_Dt_Tokenizer {
    private $options = array();
    public function __construct($options = array()) {
        $this->options = $options;
        
        if ($this->options['TRIM_TAGS'])
            $trim = '(?:\r?\n)?';

        $this->pattern = ('/\G(.*?)(?:' .
            preg_quote($this->options['BLOCK_START']). '(.*?)' .preg_quote($this->options['BLOCK_END']) . $trim . '|' .
            preg_quote($this->options['VARIABLE_START']). '(.*?)' .preg_quote($this->options['VARIABLE_END']) . '|' .
            preg_quote($this->options['COMMENT_START']). '(.*?)' .preg_quote($this->options['COMMENT_END']) . $trim . ')/sm'
        );
    }

    /**
     * parse given source into token list.
     *
     * @param string $source 
     * @return Doggy_Dt_TokenStream
     */
    public function tokenize($source) {
        $result = new Doggy_Dt_TokenStream;
        $pos = 0;
        $matches = array();
        preg_match_all($this->pattern, $source, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            if (isset($match[1]) && ($match[1] !== '')) {
                $result->feed('text', $match[1], $pos);
            }
            $tagpos = $pos + strlen($match[1]);
            if ($match[2]) {
                $result->feed('block', trim($match[2]), $tagpos);
            }
            elseif ($match[3]) {
                $result->feed('variable', trim($match[3]), $tagpos);
            }
            elseif ($match[4]) {
                $result->feed('comment', trim($match[4]), $tagpos);
            }
            $pos += strlen($match[0]);
        }
        if ($pos < strlen($source)) {
            $result->feed('text', substr($source, $pos), $pos);
        }
        $result->close();
        return $result;
    }
}
?>