<?php
/**
 * 使用PHP内置Session机制来实现Session的存储
 */
class Doggy_Session_Storage_Php extends Doggy_Object implements Doggy_Session_Storage {
    private $_session_key;
    private $_session_id='';
    public function __construct(){
        $key = Doggy_Config::get('app.session.id');
        if(empty($key)){
            $key = Doggy_Config::$vars['app.id'].'_session';
        }
        $this->_session_key = $key;
    }

    public function init(){
        Doggy_Log_Helper::debug('fetch session key:'.$this->_session_key.' session_id:'.session_id(),__CLASS__);
        $data = isset($_SESSION[$this->_session_key])?$_SESSION[$this->_session_key]:array();
        Doggy_Log_Helper::debug('data:'.@implode('',$data),__CLASS__);
        session_write_close();
        return $data;
    }
    public function store($data){
        //@session_id(Doggy_Session_Context::getSessionId());
        @session_start();
        Doggy_Log_Helper::debug('store data:'.@implode('',$data).' key:'.$this->_session_key.' session_id:'.session_id(),__CLASS__);
        $_SESSION[$this->_session_key] = $data;
        session_write_close();
    }
}
/** vim:sw=4:expandtab:ts=4 **/
?>