<?php
/**
 *
 * Smarty Result
 *
 * @author Night
 */
class Doggy_Dispatcher_Result_Smarty extends  Doggy_Dispatcher_Result_Abstract  {
    private $smarty=null;
    public function init(){
        $smarty= $this->getSmarty();
        if(!$smarty->isInitialized()){
            $smarty->initRuntimeDirectory();
        }
    }
    public function render(){ 
        
        $stash = Doggy_Dispatcher_Util::merge_stash($this->invocation);
        
        $content_type = isset($stash['_view']['content_type'])?$stash['_view']['content_type']:null;
        $charset = isset($stash['_view']['charset'])?$stash['_view']['charset']:'utf-8';
        if(!is_null($content_type)){
            $response = $this->invocation->getInvocationContext()->getResponse();
            $response->setContentType($content_type)->setCharacterEncoding($charset);
        }
        $tpl = $stash['_view']['template'];
        
        $smarty = $this->getSmarty();
        if(!$smarty->isResourceReadable($tpl)){
            throw new Doggy_Dispatcher_Result_Exception('Template:'.$tpl.' isnt readable!');
        }
        unset($stash['_view']);
        foreach ($stash as $k=>$v){
            $smarty->assign($k,$v);
        }
        $content = $smarty->fetch($tpl);
        $this->setBuffer($content);
        $this->executed=true;
    }
    /**
     * Returns current Smarty instance
     *
     * @return Doggy_Util_Smarty
     */
    protected  function getSmarty(){
        if(is_null($this->smarty)){
            $this->smarty = Doggy_Util_Smarty::factory();
        }
        return $this->smarty;
    }
    public function setSmarty($smarty){
        $this->smarty=$smarty;
        return $this;
    }
}
/* vim: set expandtab  tabstop=4 sw=4 :*/
?>