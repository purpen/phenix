<?php
/**
 * Log 抽象类
 *
 * @author night
 * @version $Id:Abstract.php 6369 2007-10-18 07:17:33Z night $
 */
abstract class Doggy_Log_Abstract implements Doggy_Log   {

    const OFF_LEVEL=-1;
    const FATAL_LEVEL=0;
    const ERROR_LEVEL=100;
    const WARN_LEVEL=200;
    const INFO_LEVEL=300;
    const DEBUG_LEVEL=400;
    const ALL_LEVEL=1000;
    private $logLevel;

    private static $levelNames = array(
       'off'=>self::OFF_LEVEL,
       'fatal'=>self::FATAL_LEVEL,
       'error'=>self::ERROR_LEVEL,
       'warn'=>self::WARN_LEVEL,
       'info'=>self::INFO_LEVEL,
       'debug'=>self::DEBUG_LEVEL,
       'all'=>self::ALL_LEVEL
    );
    public function __construct($options=array()){
       $this->setOption($options);
    }
    /**
     * 设置每个具体的Log实现的相关的参数
     *
     * @param array $options
     */
    protected function setOption($options=array()){
        if(isset($options['level'])){
            $this->setLogLevel($options['level']);
        }
    }

    /**
     * Set current logging level.
     *
     * @param mixed $levelName ethier 'OFF'|'FATAL'|'ERROR'|'WARN'|'INFO'|'DEBUG'|'ALL' string
     * or a custom integer value.
     */
    public function setLogLevel($levelName){
        $name = strtolower($levelName);
        $this->logLevel = isset(self::$levelNames[$name])?self::$levelNames[$name]:(int)$levelName;
        return $this;
    }
    /**
     * Output a message into log file.
     *
     * @param string $type
     * @param string $message
     * $param string $sender
     * @access private
     */
    protected  abstract function _output($type,$message,$sender);
    /**
     * Output a DEBUG level message
     *
     * @param string $message
     * @param string $sender
     * @return Doggy_Log
     */
    public function debug($message,$sender){
        if( !$this->isDebugEnabled() )return;
        $this->_output('DEBUG',$message,$sender);
        return $this;
    }
    /**
     * Output INFO level message
     *
     * @param string $message
     * @param string $sender
     * @return Doggy_Log
     */
    public function info($message,$sender){
        if( !$this->isInfoEnabled())return;
        $this->_output('INFO',$message,$sender);
        return $this;
    }
    /**
     * Output a WARN level message
     * @param string $message
     * @param string $sender
     * @return Doggy_Log
     */
    public function warn($message,$sender){
        if( !$this->isWarnEnabled())return;
        $this->_output('WARN',$message,$sender);
        return $this;
    }
    /**
     * Output a ERROR level message
     * @param string $message
     * @param string $sender
     * @return Doggy_Log
     */
    public function error($message,$sender){
        if( !$this->isErrorEnabled() )return;
        $this->_output('ERROR',$message,$sender);
        return $this;
    }
    /**
     * Output a FATAL level message
     * @param string $message
     * @param string $sender
     * @return Doggy_Log
     */
    public function fatal($message,$sender){
        if(!$this->isFatalEnabled() )return;
        $this->_output('FATAL',$message,$sender);
    }
    /**
     * Return is enable FATAL level message
     * @return boolean
     */
    function isFatalEnabled(){
        return $this->logLevel >= self::FATAL_LEVEL;
    }
    /**
     * Return is enable output ERROR level message
     */
    function isErrorEnabled(){
        return $this->logLevel >= self::ERROR_LEVEL;
    }
    /**
     * Return is enable output WARN level message
     */
    function isWarnEnabled(){
        return $this->logLevel >= self::WARN_LEVEL;
    }
    /**
     * Return is enable output INFO level message
     */
    function isInfoEnabled(){
        return $this->logLevel >= self::INFO_LEVEL;
    }
    /**
     * Return is enable output DEBUG level message
     */
    function isDebugEnabled(){
        return $this->logLevel >= self::DEBUG_LEVEL;
    }
}
?>