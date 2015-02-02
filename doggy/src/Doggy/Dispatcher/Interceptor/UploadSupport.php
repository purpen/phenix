<?php
class Doggy_Dispatcher_Interceptor_UploadSupport extends Doggy_Dispatcher_Interceptor_Abstract {
    private $_uploads=array();
    public function intercept(Doggy_Dispatcher_ActionInvocation $invocation){
        $this->checkUpload($invocation);
        return $invocation->invoke();
    }
    protected function checkUpload(Doggy_Dispatcher_ActionInvocation $invocation){
        $action = $invocation->getAction();
        if (!$action instanceof Doggy_Dispatcher_Action_Interface_UploadSupport) {
        	return;
        }
        $req = $invocation->getInvocationContext()->getRequest();
        if(!$req instanceof Doggy_Dispatcher_Request_Http ){
            Doggy_Log_Helper::debug("Current request not support file upload,skip");
            return;
        }
        $files = $req->getUploadFiles();
        foreach($files as $k=>$f){
            Doggy_Log_Helper::debug("Process field:$k");
            //check single file or file array
            if(is_array($f['name'])){
                Doggy_Log_Helper::debug("multi file array,revert it..");
                $_files = $this->_revertMultiFiles($f,$k);
            }else{
                $f['id']=$k;
                $_files = array($f);
            }
            $this->mergeUploads($_files,$req);
        }
        $action->setUploadFiles($this->_uploads);
        $this->_uploads=array();
    }
    private function mergeUploads($files,$req){
        foreach($files as $f){
            switch ($f["error"]) {
              case UPLOAD_ERR_OK:
                  $path = $f['tmp_name'];
                  $type = $f['type'];
                  $id   = $f['id'];
                  $name = $f['name'];
                  $size = $f['size'];
                  if(!$req->isUploadedFile($path)){
                      Doggy_Log_Helper::warn("$id is not a uploaded file,skip");
                      continue;
                  }
                  $this->_uploads[] = array('path'=>$path,'id'=>$id,'type'=>$type,'name'=>$name,'size'=>$size);
                  break;
              case UPLOAD_ERR_INI_SIZE:
                  Doggy_Log_Helper::warn("The uploaded file exceeds the upload_max_filesize directive (".ini_get("upload_max_filesize").") in php.ini.",__CLASS__);
                  continue;
              case UPLOAD_ERR_FORM_SIZE:
                  Doggy_Log_Helper::warn("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.",__CLASS__);
                  continue;
              case UPLOAD_ERR_PARTIAL:
                  Doggy_Log_Helper::warn("The uploaded file was only partially uploaded.");
                  continue;
              case UPLOAD_ERR_NO_FILE:
                  Doggy_Log_Helper::warn("No file was uploaded.");
                  continue;
              case UPLOAD_ERR_NO_TMP_DIR:
                  Doggy_Log_Helper::warn("Missing a temporary folder.");
                  continue;
              case UPLOAD_ERR_CANT_WRITE:
                  Doggy_Log_Helper::warn("Failed to write file to disk");
                  continue;
              default:
                  Doggy_Log_Helper::warn("Unknown File Error");
            }
                        
        }
    }
    private function _revertMultiFiles($file_post,$id){
        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);
        for ($i=0; $i<$file_count; $i++) {
           foreach ($file_keys as $key) {
               $file_ary[$i][$key] = $file_post[$key][$i];
           }
           $file_ary[$i]['id']=$id;
        }
       return $file_ary;
    }
}
/**vim:sw=4 et ts=4 **/
?>