<?php
/**
 * Default Action for doggy framework
 *
 * If no any custom action found, the framework will fallback to this.
 *
 */
class Doggy_Action_Doggy extends Doggy_Dispatcher_Action_Base {
    private $doggy='Doggy parameters test,please set doggy param value.';
    /**
     * Just returns a welcome infomation smarty result.
     *
     * @return string
     */
    public function execute(){
        $session = $this->getContext()->getSessionContext();
        if($session->has('doggy_visits')){
            $session->set('doggy_visits',$session->get('doggy_visits')+1);
        }else{
            $session->set('doggy_visits',1);
        }
        
        $this->putContext('visits',$session->get('doggy_visits'));
        $this->putContext('params',$this->getContext()->getRequest()->getParams());
        
        $dispatcher_info = Doggy_Config::get('app.dispatcher');
        $this->putContext('dispatcher_info', $dispatcher_info);
        $this->putContext('modules',Doggy_Config::get('app.modules'));
        $this->putContext('doggy',$this->getDoggy());
        $this->putContext('doggy_version',DOGGY_VERSION);
        
        
        // return $this->smartyResult('doggy.welcome');
        return $this->dtResult('doggy/default.html');
    }
    public function getDoggy(){
        return $this->doggy;
    }
    public function setDoggy($id){
        $this->doggy=$id;
        return $this;
    }
    
    public function jquery(){
        return $this->jqueryResult('doggy.jquery',true);
    }
    
}
?>