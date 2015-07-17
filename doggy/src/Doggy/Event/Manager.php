<?php
/**
 * MQ Subject 管理类
 * 
 * 本类实现一个消息队列的发送
 * 
 * @version $Id: Manager.php 7169 2007-11-11 07:19:14Z night $
 * @author night
 */
class Doggy_Event_Manager {
    /**
     * @var Doggy_Event_Manager
     */
    private static $_instance=null;
    private $_subjects;
    private $_observers;
    
    protected function __construct(){
        $subjects = Doggy_Config::get('app.event.subjects');
        Doggy_Log_Helper::debug("initialize subjects...");
        $i=0;
        foreach($subjects as $key=>$class){
            $subject = new $class();
            if(!$subject instanceof Doggy_Event_Subject){
                Doggy_Log_Helper::error("Invalid subject $key:$class");
                throw new Doggy_Event_Exception("Invalid subject $key:$class");
            }
            $this->_subjects[$key]= $subject;
            $i++;
        }
        Doggy_Log_Helper::debug("Total subjects:$i intialized.");
        
        $observers = Doggy_Config::get('app.event.observers');
        foreach($observers as $class=>$subjects){
            $observer = new $class();
            if(!$observer instanceof Doggy_Event_Observer){
                Doggy_Log_Helper::error("Invalid observer:$class");
                throw new Doggy_Event_Exception("Invalid observer:$class");
            }
            foreach($subjects as $subject){
                $obj = $this->getSubject($subject);
                if(is_null($obj)){
                    Doggy_Log_Helper::warn("unknown subject:$subject,observer:$class,ignore it.");
                    continue;
                }
                Doggy_Log_Helper::debug("[Observer:$class] Listen to subject:$subject ");
                $obj->addListener($observer);
            }
        }
    }
    /**
     * 检索指定名称的subject对象
     * 
     * @param string $name
     * @return Doggy_Event_Subject
     */
    public function getSubject($name){
        return isset($this->_subjects[$name])?$this->_subjects[$name]:null;
    }
    /**
     * 创建并返回singleton实例
     * 
     * @return Doggy_Event_Manager
     */
    public static function getInstance(){
        if(is_null(self::$_instance)){
            self::$_instance = new Doggy_Event_Manager();
        }
        return self::$_instance;
    }
    
    public static function reset(){
        self::$_instance = null;
    }
    /**
     * 发送指定的subject类型的消息
     *
     * @param mixed $message
     * @param string $subjectName
     */
    public static function sendMessage($subjectName,$message){
        self::getInstance()->getSubject($subjectName)->notifyListeners($message);
    }
}
/**vim:sw=4 et ts=4 **/
?>