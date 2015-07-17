<?php
/**
 * A token stream used in parsing template.
 *
 */
class Doggy_Dt_TokenStream {
    private $pushed = array();
    private $stream = array();
    private $closed = false;
    private $c;

    /**
     * Popup stream last token.
     *
     * @return Doggy_Dt_Token
     */
    function pop() {
        if (count($this->pushed)) {
            return array_pop($this->pushed);
        }
        return array_pop($this->stream);
    }
    
    /**
     * Create a token with given type,content,position, push into stream.
     *
     * @param string $type 
     * @param string $contents 
     * @param int $position 
     * @return void
     */
    function feed($type, $contents, $position) {
        if ($this->closed) {
            throw new Doggy_Dt_Exception('cannot feed closed stream');
        }
        $this->stream[] = new Doggy_Dt_Token($type, $contents, $position);
    }

    /**
     * push a token into stream.
     *
     * @param string $token 
     * @return void
     */
    function push($token) {
        if (is_null($token))
            throw new Doggy_Dt_Exception('cannot push NULL');
        if ($this->closed)
            $this->pushed[] = $token;
        else
            $this->stream[] = $token;
    }
    
    /**
     * Close stream,ready to interate.
     *
     * @return void
     */
    function close() {
        if ($this->closed)
            throw new Doggy_Dt_Exception('cannot close already closed stream');
        $this->closed = true;
        $this->stream = array_reverse($this->stream);
    }
    
    /**
     * the stream is closed.
     *
     * @return boolean
     */
    function is_closed() {
        return $this->closed;
    }

    /**
     * Current token in stream
     *
     * @return Doggy_Dt_Token
     */
    function current() {
        return $this->c ;
    }
    /**
     * Move and return next token int stream.
     *
     * @return Doggy_Dt_Token
     */
    function next() {
        return $this->c = $this->pop();
    }    
}
?>