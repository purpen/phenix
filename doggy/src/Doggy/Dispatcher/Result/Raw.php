<?php
/**
 * 直接输出裸数据
 */
class Doggy_Dispatcher_Result_Raw extends Doggy_Dispatcher_Result_Abstract {
    protected function render(){
        
        $stash = Doggy_Dispatcher_Util::merge_stash($this->invocation);
        $data = isset($stash['_view']['data'])?$stash['_view']['data']:'';
        $content_type = isset($stash['_view']['content_type'])?$stash['_view']['content_type']:null;
        $charset = isset($stash['_view']['charset'])?$stash['_view']['charset']:'utf-8';
        $status_code = isset($stash['_view']['status_code'])?$stash['_view']['status_code']:200;
        
        $response = $this->invocation->getInvocationContext()->getResponse();
        $response->setHttpResponseCode($status_code);
        if(!empty($content_type)){
            $response->setContentType($content_type)->setCharacterEncoding($charset);
        }
        $this->buffer = $data;
        $this->executed=true;
    }
}
/**vim:sw=4 et ts=4 **/
?>