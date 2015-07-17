<?php
/**
 * Special page that direct output a MongoGridFSFile to client.
 *
 * This class support E-Tag,Exipres http feature, save bandwidth.
 * 
 * BUT, WARNING: 
 * 
 * THIS IS JUST PROTOTYPE AND ONLY USAGE IS FOR QUICK DEV/TEST! 
 * DONT USE IT AS PRODUCTION DEPLOYMENT!
 * WHY? POOL PHP PERFORMANCE!!!
 * 
 * p.s. My production is a standalone/hight performance PLACK application/webserver ;-)
 * 
 * @author n.s.
 */
class DoggyX_View_MongoFs extends Doggy_Dispatcher_Result_Abstract {
    protected $options = array(
        'x'=>false,
        );
    public function init() {
    }
    public function render(){
        $stash = Doggy_Dispatcher_Util::merge_stash($this->invocation);
        $fs = $stash['_view']['fs'];
        
        
        if (!$fs instanceof MongoGridFSFile) {
           throw new Doggy_Dispatcher_Exception('Invalid MongoGridFS!');
        }
        $fs_meta = $fs->file;
        
        $response = $this->invocation->getInvocationContext()->getResponse();
        $request  = $this->invocation->getInvocationContext()->getRequest();
        
        $last_modified_time = $fs_meta['time'];
        
        //检查HEADER, 如果文件没有变化则跳过输出,直接304
        $etag = '"'.$fs_meta['md5'].'"';
        $request_headers = $request->getHeaders();
        $expired = DoggyX_Util_HttpCacheValidator::is_expired($last_modified_time,$etag,$request_headers);

        if (!empty($fs_meta['mime_type'])) {
            $response->setContentType($fs_meta['mime_type']);
        }
        if (!$expired) {
            $response->setRawHeader("Expires: " . gmdate("r", time()+864000));
            $response->setHttpResponseCode(304);
        }
        else {
            // 输出文件内容
            if (!empty($fs_meta['time'])) {
                $response->setHeader('Last-Modified',gmdate('r',$fs_meta['time'])); 
            }
            // 输出ETAG
            $response->setRawHeader("ETag: $etag");
            $response->setHeader('Content-Length',$fs->getSize());
            // expires 10 days
            $response->setRawHeader("Expires: " . gmdate("r", time()+864000));
            if ($this->options['x']) {
                $response->setHeader('X-MongoFS',$fs->file['_id']);
            }
            else {
                $this->buffer = $fs->getBytes();
                unset($fs);
            }
        }
        $this->executed=true;
    }
}