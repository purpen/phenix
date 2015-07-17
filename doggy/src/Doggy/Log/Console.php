<?php
class Doggy_Log_Console extends Doggy_Log_Abstract {
    /**
     * Output a message into log file.
     *
     * @param string $type
     * @param string $message
     * $param string $sender
     * @access private
     */
    protected function _output($type,$message,$sender){
		$bad = array("\n", "\r", "\t");
        $good = ' ';
        $content = str_replace($bad, $good, $message);
        fwrite(STDERR,date('y-m-d H:i:s').' '.$type." $sender - $content\n");
    }
}
/** vim:sw=4:expandtab:ts=4 **/
?>