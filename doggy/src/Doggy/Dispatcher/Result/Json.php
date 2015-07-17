<?php
/**
 * A Result that support output JSON to client.
 *
 * This result depends on php-json extension
 * 
 */
class Doggy_Dispatcher_Result_Json extends Doggy_Dispatcher_Result_Abstract  {

    protected function render(){
        $stash = Doggy_Dispatcher_Util::merge_stash($this->invocation);
        
        $jsonData = array();
        $jsonData['errorCode'] = isset($stash['_view']['error_code']) ? $stash['_view']['error_code']:null;
        $jsonData['errorMessage'] = isset($stash['_view']['error_message']) ? $stash['_view']['error_message']:null;
        if (!isset($jsonData['errorCode']) || $jsonData['errorCode'] == 200) {
            $jsonData['hasError'] = false;
        }
        else {
            $jsonData['hasError'] = true;
        }
        unset($stash['_view']);
        $jsonData['data'] = isset($stash['_json'])?$stash['_json']:null;
        $output = json_encode($jsonData);
        $this->invocation->getInvocationContext()->getResponse()->setBuffer($output)->setCharacterEncoding('utf-8');
    }
}
?>