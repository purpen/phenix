<?php
/**
 * @version $Id: Basic.php 6768 2007-10-28 12:38:12Z night $
 * @author night
 */
class Doggy_Event_Subject_Basic implements Doggy_Event_Subject {
    
    protected $_listeners=array();
    /**
     * @param Doggy_Event_Observer $listner
     */
    public function addListener($listner){
        if(!$listner instanceof Doggy_Event_Observer){
            throw new Doggy_Event_Exception('listener is not a valid Doggy_Event_Observer!');
        }
        $this->_listeners[]=$listner;
    }
    /**
     * @param Doggy_Event_Observer $listener
     */
    public function removeListener($listener){
        for($i=0,$c=count($this->_listeners);$i<$c;$i++){
            if($this->_listeners[$i]==$listener){
                unset($this->_listeners[$i]);
                return;
            }
        }
    }
    /**
     * @param mixed $message
     */
    public function notifyListeners($message){
        for($i=0,$c=count($this->_listeners);$i<$c;$i++){
            $this->_listeners[$i]->update($message);
        }
    }
}
/**vim:sw=4 et ts=4 **/
?>