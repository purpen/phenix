<?php
class Doggy_Mock_Request extends Doggy_Dispatcher_Request_Http {
    private $_files=array();
    
    /**
     * mock add cookie value
     * 
     * @param string $name
     * @param string $value
     * @return Doggy_Mock_Request
     */
    public function addCookie($name,$value){
        $_COOKIE[$name]=$value;
        return $this;
    }
    /**
     * mock add a http header
     * 
     * @param string $name
     * @param mixed $value
     * @return Doggy_Mock_Request
     */
    public function addHeader($name,$value){
        $_SERVER['HTTP_'.strtoupper(str_replace('-','_',$name))]=$value;
        return $this;
    }
    
    /**
     * override allow mock upload
     * 
     * @param string $file
     * @return boolean
     */
    public function isUploadedFile($file){
        return in_array($file,$this->_files);
    }
    /**
     * 模拟文件上传的行为以便测试
     * 
     * @param mixed $name
     * @param string $file
     * @return Doggy_Mock_Request
     */
    public function mockUpload($name,$file=null){
        if(is_array($name)){
            foreach($name as $k=>$v){
                if(is_array($v)){
                    $_FILES[$k]=array();
                    foreach($v as $_f){
                        if(is_readable($_f)){
                            $size = filesize($_f);
                            $type = Doggy_Util_File::mime_content_type($_f);
                            $tmp_name = 'tmp_'.hash('md5',$file.'_'.microtime()).'_'.rand(20,2000);
                            $name = basename($_f);
                            $to = '/tmp/'.$tmp_name;
                            $error=UPLOAD_ERR_OK;
                            copy($_f,$to);
                            $this->_files[]=$to;
                        }else{
                            $to=null;
                            $type=null;
                            $name=basename($_f);
                            $size=-1;
                            $error=UPLOAD_ERR_NO_FILE;
                        }
                        $_FILES[$k]['tmp_name'][]=$to;
                        $_FILES[$k]['type'][]=$type;
                        $_FILES[$k]['name'][]=$name;
                        $_FILES[$k]['size'][]=$size;
                        $_FILES[$k]['error'][]=UPLOAD_ERR_OK;
                    }
                }else{
                    $this->mockUploadFile($k,$v);
                }
            }
        }else{
            $this->mockUploadFile($name,$file);
        }
        return $this;
    }
    private function mockUploadFile($name,$file){
        if(is_readable($file)){
            $size = filesize($file);
            $type = Doggy_Util_File::mime_content_type($file);
            $tmp_name = 'tmp_'.hash('md5',$file.'_'.microtime()).'_'.rand(20,2000);
            $to = '/tmp/'.$tmp_name;
            copy($file,$to);
            $_FILES[$name] = array('tmp_name'=>$to,'type'=>$type,'name'=>basename($file),'size'=>$size,'error'=>UPLOAD_ERR_OK);
            $this->_files[]=$to;
        }else{
            $_FILES[$name] = array('tmp_name'=>null,'type'=>null,'name'=>basename($file),'size'=>-1,'error'=>UPLOAD_ERR_NO_FILE);
        }
    }
}