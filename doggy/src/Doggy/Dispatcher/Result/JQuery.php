<?php
/**
 * 返回jQuery
 * 的taconite plugin支持的xmldocument文档格式
 */
class Doggy_Dispatcher_Result_JQuery extends Doggy_Dispatcher_Result_Smarty {
    public function init(){
        parent::init();
        $smarty= $this->getSmarty();
    }
    public function render(){
        
        
        parent::render();
        
        $stash = Doggy_Dispatcher_Util::merge_stash($this->invocation);
        
        $crossDomain = isset($stash['_view']['crossDomain']) ? $stash['_view']['crossDomain']:false;
        $callback    = isset($stash['_view']['callback']) ? $stash['_view']['callback']:null;
        
        $response = $this->invocation->getInvocationContext()->getResponse();
        $response->setCharacterEncoding('utf-8');
        $response->setRawHeader('Cache-Control:no-store,no-cache, must-revalidate,private,pre-check=0, post-check=0, max-age=0,max-stale=0')
        ->setRawHeader('Expires:Mon, 23 Jan 1978 12:52:30 GMT')
        ->setRawHeader('Pragma:no-cache');
        
        $content = "<taconite>\n".$this->buffer."</taconite>";
        if($crossDomain){
            $content = Doggy_Util_String::escapeJavascriptString($content);
            if($callback){
                $content = $callback.'"'.$content.'");';
            }else{
                $content = '$.taconite("'.$content.'");';
            }
            $response->setContentType('text/javascript');
        }else{
            $response->setContentType('text/xml');
        }
        $this->buffer = $content;
    }
}
/**vim:sw=4 et ts=4 **/
?>