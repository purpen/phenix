<?php
/**
 * 实现Doggy_Log接口,将日志输出到制定的日志文件
 *
 * @author night
 * @version $Id: FileLog.php 6369 2007-10-18 07:17:33Z night $
 */
class Doggy_Log_FileLog extends Doggy_Log_Abstract {

    private $logFile=null;
    private $_fp;
    
    public  function __destruct() {
        if (is_resource($this->_fp)) {
            @fclose($this->_fp);
        }
    }
    protected function setOption($options=array()){
        if(isset($options['output'])){
            $log_file = $options['output'];
            if ($log_file{0} != "/") {
                $log_file = DOGGY_APP_ROOT.'/logs/'.$log_file;
            }
            $this->setOutput($log_file);
        }
        parent::setOption($options);
    }
    /**
     * Set filename used  to log.
     *
     * @param string $file
     */
    function setOutput($file){
        $this->logFile = $file;
        if (is_resource($this->_fp)) {
            @fclose($this->_fp);
        }
        $this->_fp = fopen($file,'a+');
    }
    /**
     * Output a message into log file.
     *
     * @param string $type
     * @param string $message
     * @param string $sender
     * @access private
     */
    protected function _output($type,$message,$sender){
       # $bad = array("\n", "\r", "\t");
       # $good = ' ';
       # $content = str_replace($bad, $good, $message);
        /*
        if(!is_null($this->logFile)){
            error_log(date('y-m-d H:i:s').' '.$type." $sender - $message\n",3,$this->logFile);
        }else{
            error_log(date('y-m-d H:i:s').' '.$type." $sender - $message\n");
        }
        */
        @fwrite($this->_fp,date('y-m-d H:i:s').' '.$type." $sender - $message\n");
        @flush($this->_fp);
    }
}
?>