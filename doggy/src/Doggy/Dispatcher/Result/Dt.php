<?php
class Doggy_Dispatcher_Result_Dt extends  Doggy_Dispatcher_Result_Abstract {
    
    protected $dt_options;
    protected $dt;
    
    public function init() {
        $dt_options = Doggy_Config::get('app.dt');
        
        if (!isset($dt_options['templates_dir']) || !is_dir($dt_options['templates_dir'])) {
            $dt_options['searchpath'] = DOGGY_APP_ROOT.'/templates';
        }
        else {
            $dt_options['searchpath'] = $dt_options['templates_dir'];
        }
        $this->dt_options = $dt_options;
        $this->dt = new Doggy_Dt(null,$this->dt_options);
    }
    
    public function render(){ 
        $stash = Doggy_Dispatcher_Util::merge_stash($this->invocation);
        $content_type = isset($stash['_view']['content_type'])?$stash['_view']['content_type']:null;
        $charset = isset($stash['_view']['charset'])?$stash['_view']['charset']:'utf-8';
        $response = $this->invocation->getInvocationContext()->getResponse();
        if(!is_null($content_type)){
            $response->setContentType($content_type)->setCharacterEncoding($charset);
        }
        try{
            $tpl = $stash['_view']['template'];
            $context = $stash;
            unset($context['_view']);
            $dt = $this->dt;
            $dt->set($context);
            $dt->load_template($tpl);
            $output = $dt->render();
            $this->setBuffer($output);
            // echo "Content-Length:".strlen($output);
            // $response->setRawHeader('Content-Length: '.strlen($output));
            $this->executed=true;
        }catch(Doggy_Dt_Exception_TemplateNotFound $e) {
            Doggy_Log_Helper::error("template not found,template path:".$e->getMessage());
            throw new Doggy_Dispatcher_Result_Exception("template not found,template path:".$e->getMessage());
        }catch(Doggy_Dt_Exception_TemplateSyntaxError $e) {
            Doggy_Log_Helper::error("template< $tpl > syntax error:".$e->getMessage());
            throw new Doggy_Dispatcher_Result_Exception("template< $tpl > syntax error:".$e->getMessage());
        }catch(Doggy_Dt_Exception $e) {
            Doggy_Log_Helper::error('unkonwn dt runtime error,caused by:'.$e->getMessage());
            throw new Doggy_Dispatcher_Result_Exception('unkonwn dt runtime error,caused by:'.$e->getMessage());
            
        }
    }
}
?>