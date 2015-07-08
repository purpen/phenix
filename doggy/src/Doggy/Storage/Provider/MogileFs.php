<?php
/**
 * Doggy_Storage_Provider_MogileFS
 * 
 * 使用mogilefs作为后端的存储系统
 */
class Doggy_Storage_Provider_MogileFs implements Doggy_Storage_Provider {
    /**
     * MogileFS client
     *
     * @var Doggy_Util_MogileFs_Client
     */
    private $fs;
    
    public function __construct($options=array()){
        $hosts=null;
        $domain=null;
        $class=null;
        extract($options,EXTR_IF_EXISTS);
        if(empty($hosts)){
            throw new Doggy_Storage_Exception('you must specific host');
        }
        if(empty($domain)){
            throw new Doggy_Storage_Exception('mogilefs domain cannot be null');
        }
        if(empty($class)){
            throw new Doggy_Storage_Exception('mogilefs class cannot be null');
        }
        $this->fs= new Doggy_Util_MogileFs_Client($domain,$class,$hosts);
    }
    /**
     * 将以指定的Key保存数据
     *
     * @param string $id
     * @param string $data
     * @return Doggy_Storage_Provider_MogileFs
     */
    public function store($id,$data){
        try{
            $this->fs->set($id,$data);
        }catch(Doggy_Util_MogileFs_Exception $e){
            Doggy_Log_Helper::error("stored [ $id ] failed:".$e->getMessage(),__CLASS__);
            throw new Doggy_Storage_Exception("MogileFs failed:".$e->getMessage());
        }
        return $this;
    }
    /**
     * 将本地文件保存到后端
     *
     * @param string $id
     * @param string $file
     * @return Doggy_Storage_Provider_MogileFs
     */
    public function storeFile($id,$file){
        if(!is_readable($file)){
            Doggy_Log_Helper::error('File is not readable:'.$file,__CLASS__);
            throw new Doggy_Storage_Exception('Local file is not readable:'.$file);
        }
        try{
            $this->fs->setFile($id,$file);
        }catch(Doggy_Util_MogileFs_Exception $e){
            throw new Doggy_Storage_Exception("MogileFs failed:".$e->getMessage());
        }
        return $this;
    }
    /**
     * 删除指定id的数据
     *
     * @param string $id
     * @return Doggy_Storage_Provider_MogileFs
     */
    public function delete($id){
        try{
            $this->fs->delete($id);
        }catch(Doggy_Util_MogileFs_Exception $e){
            throw new Doggy_Storage_Exception("MogileFs failed:".$e->getMessage());
        }
        return $this;
    }
    /**
     * 以字符串形式返回指定id的数据内容
     *
     * @param string $id
     * @return string
     */
    public function get($id){
        return $this->fs->get($id);
    }
    /**
     * 返回后端指定id的数据的Path以便客户端可以用fopen进行后续操作
     *
     * @param string $id
     * @return string
     */
    public function getPath($id){
        try{
            $path = $this->fs->getPaths($id);
        }catch(Doggy_Util_MogileFs_Exception $e){
            throw new Doggy_Storage_Exception("MogileFs failed:".$e->getMessage());
        }
        if(empty($path)){
            return null;
        }
        $i=0;
        //if path more than 2,should random pick one
        if(count($path)>2){
            $i = rand(0,count($path)-1);
        }
        return $path[$i];
    }
    /**
     * 返回指定id的uri访问地址（如果可能)
     * 
     * 如果资源不存在或不支持uri访问则返回null
     *
     * @param string $id
     * @return string
     */
    public function getUri($id){
        return $this->getPath($id);
    }
    /**
     * 检测是否已经存在指定id的数据
     *
     * @param string $id
     * @return boolean
     */
    public function exists($id){
        return $this->fs->exists($id);
    }
    /**
     * 复制指定id的数据到新的id,若新的id已经存在则将被覆盖
     * 
     * 注意：复制是无条件复制,因此客户端应自行检查是否存在目标id
     *
     * @param string $id
     * @param string $copyId
     * @return Doggy_Storage_Provider_MogileFs
     */
    public function copy($id,$copyId){
        try{
            $data = $this->get($id);
            $this->store($copyId,$data);
        }catch(Doggy_Util_MogileFs_Exception $e){
            Doggy_Log_Helper::error("Faild when copy $id to $copyId,error:".$e->getMessage());
            throw new Doggy_Storage_Exception("MogileFs failed:".$e->getMessage());
        }
        return $this;
    }
    /**
     * 将旧id修改为新的id
     *
     * 如旧id不存在则抛出一个异常。
     * 
     * @param string $oldId
     * @param string $newId
     * @return Doggy_Storage_Provider_MogileFs
     */
    public function rename($oldId,$newId){
        try{
            $this->fs->rename($oldId,$newId);
        }catch(Doggy_Util_MogileFs_Exception $e){
            Doggy_Log_Helper::error("Faild when rename:".$e->getMessage());
            throw new Doggy_Storage_Exception("Faild when rename:".$e->getMessage());
        }
        return $this;
    }
}
?>