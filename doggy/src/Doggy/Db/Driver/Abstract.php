<?php
/**
 * 实现Doggy_Db_Driver 接口的抽象类
 *
 * 所有的Driver应该继承本类并实现其中的抽象方法
 *
 */
abstract class Doggy_Db_Driver_Abstract extends Doggy_Object implements Doggy_Db_Driver {

    protected $dsn;
    protected $uri;
    protected $args;
    protected $_connected=false;

    /**
     * Do real connection
     *
     * @return bool
     */
    abstract protected function do_connect();
    /**
     * Close real connection
     *
     */
    abstract protected function do_close();

    /**
     * 构造函数
     *
     * 解析dsn到相应的url格式
     *
     * @param string $dsn
     *
     */
    public function __construct($dsn) {
        $uri = parse_url($dsn);
        if(isset($uri["query"])){
            parse_str($uri['query'],$args);
        }else{
            $args=null;
        }
        $this->dsn = $dsn;
        $this->uri = $uri;
        $this->args = $args;

    }
    
    public function __destruct(){
        $this->close();
    }

    public function connect(){
        if(!$this->_connected){
            $this->_connected = $this->do_connect();
        }
        return $this->_connected;
    }
    
    public function close(){
        if($this->_connected){
            $this->do_close();
        }
    }
}
?>