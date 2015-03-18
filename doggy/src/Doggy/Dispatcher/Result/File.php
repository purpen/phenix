<?php
/**
 * 输出指定的静态文件内容
 *
 * @version $Id:File.php 6369 2007-10-18 07:17:33Z night $
 * @author Night
 *
 */
class Doggy_Dispatcher_Result_File extends Doggy_Dispatcher_Result_Abstract {
    
    const NGINX_LOCATION='/__file_result__/';
    
    protected function render(){

        $stash = Doggy_Dispatcher_Util::merge_stash($this->invocation);
        
        $content_type = isset($stash['_view']['content_type'])?$stash['_view']['content_type']:null;
        $file = $stash['_view']['path'];
        
        if(!is_readable($file)){
            throw new Doggy_Dispatcher_Result_Exception('File:'.$file.' isnt readable!');
        }
        if(empty($content_type)){
            $content_type = Doggy_Util_File::mime_content_type($file);
            Doggy_Log_Helper::debug("mime content type:".$content_type);
        }
        
        $context = $this->invocation->getInvocationContext();
        $response = $context->getResponse();
        $response->setContentType($content_type);
        //optimize for Nginx,lighttpd
        $request = $context->getRequest();
        if ($request->isLightyServer()) {
            Doggy_Log_Helper::debug('Lighty found,X-Sendfile Actived.');
            $response->setHeader('X-Sendfile',$file);
        } elseif($request->isNginxServer()) {
            Doggy_Log_Helper::debug('Nginx found,X-Accel-Redirect Actived.');
            $response->setHeader('X-Accel-Redirect',self::NGINX_LOCATION.$file);
        }else{
            $this->buffer = @file_get_contents($file);
        }
        $this->executed=true;
    }
}
/* vim:set expandtab tabstop=4 sw=4 : */
?>