<?php
/**
 * Common log interface
 *
 * @version $Id: Log.php 6369 2007-10-18 07:17:33Z night $
 * @author Night
 */
interface Doggy_Log{
    function __construct($options=array());
    function fatal($message,$sender);
    function error($message,$sender);
    function warn($message,$sender);
    function info($message,$sender);
    function debug($message,$sender);
    function isFatalEnabled();
    function isErrorEnabled();
    function isWarnEnabled();
    function isInfoEnabled();
    function isDebugEnabled();
}
?>