<?php
/**
 * 实现Doggy_Dba_Adapter接口的抽象类
 *
 * 所有的Adapter应该继承本类并实现其中的抽象方法
 *
 */
abstract   class Doggy_Dba_Adapter_Abstract implements Doggy_Dba_Adapter {

    /**
     * Do real connection
     *
     * @return bool
     */
    abstract protected function doConnect();
    /**
     * Close real connection
     *
     */
    abstract protected function doClose();

    //abstract public function query(string $sql,int $size,int $page,array $vars);
    //abstract public function execute(string $sql,array $vars);
    //abstract public function genSeq(string $name);
    //abstract public function getFieldMetaList(string $table);
    //abstract public function getTableList();

    protected $dsn;
    protected $uri;
    protected $args;

    protected $_connected=false;

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
            $this->_connected = $this->doConnect();
        }
        return $this->_connected;
    }

    public function close(){
        if($this->_connected){
            $this->doClose();
        }
    }

    /**
     * Just compatible with EPS2006
     *
     * @deprecated
     * @see genSeq
     */
    public function genId($name){
        return $this->genSeq($name);
    }
    /**
     * Just compatible with EPS2006
     * @deprecated
     * @see query
     */
    public function pageQuery($sql,$vars=array(),$page=-1,$size=-1){
        return $this->query($sql,$size,$page,$vars);
    }
}
?>