<?php
/**
 * Doggy_Storage_Provider_FileSystem
 *
 * This provider use plain file system as backend storage.
 */
class Doggy_Storage_Provider_FileSystem implements Doggy_Storage_Provider {
    private $root;
    private $rootUrl;
    private $hashedPath=true;
    /**
     * construct
     *
     * options =>array(
     * 'root'=> 存储路径
     * 'root_url'=> root目录的url访问前缀
     * )
     *
     * @param unknown_type $option
     */
    public function __construct($options=array()){
        $root_url ='.';
        $root=null;
        $hash_dir=true;
        extract($options,EXTR_IF_EXISTS);
        if(empty($root)){
            throw new Doggy_Storage_Exception('not specific storage root directory');
        }
        if(!file_exists($root)){
            Doggy_Log_Helper::warn('storage directory is not exists,create it on:'.$root,__METHOD__);
            Doggy_Util_File::build_dir($root,0777);
        }
        $this->root = $root;
        $this->rootUrl = $root_url;
        $this->hashedPath = $hash_dir;
    }
    /**
     * 将以指定的Key保存数据
     *
     * @param string $id
     * @param string $data
     * @return Doggy_Storage_Provider_FileSystem
     */
    public function store($id,$data){
        Doggy_Log_Helper::debug('store:'.$id,__METHOD__);
        $path = $this->root.'/'.$this->_getHashPath($id);
        $this->_write($path,$data);
        return $this;
    }

    /**
     * Write data into disk
     */
    private function _write($path,$data){
        Doggy_Log_Helper::debug('write backend file:'.$path,__METHOD__);
        $ok=@file_put_contents($path,$data,LOCK_EX);
        if($ok===false){
            Doggy_Log_Helper::error('cannot create file:'.$path,__METHOD__);
            throw new Doggy_Storage_Exception('cannot store file into filesystem:'.$id);
        }
        @chmod($path,0666);
    }

    private function _getHashPath($id,$build=true){
        if($this->hashedPath){
            $hash = hash('md5',$id);
            $ext = strtolower(Doggy_Util_File::get_file_ext($id));
            if(!empty($ext)){
                $hash.='.'.$ext;
            }
            $path = substr($hash,1,2).'/'.substr($hash,2,2).'/'.$hash;
        }else{
            $path = ltrim($id,'/');
        }
        if($build){
            $dir =dirname($this->root.'/'.$path);
            if(!file_exists($dir)){
                Doggy_Util_File::build_dir($dir,0777);
            }
        }
        return $path;
    }
    /**
     * 将本地文件保存到后端
     *
     * @param string $id
     * @param string $file
     * @return Doggy_Storage_Provider_FileSystem
     */
    public function storeFile($id,$file){
        if(!is_readable($file)){
            Doggy_Log_Helper::error('File is not readable:'.$file,__METHOD__);
            throw new Doggy_Storage_Exception('Local file is not readable:'.$file);
        }
        $data = file_get_contents($file);
        return $this->store($id,$data);
    }
    /**
     * 删除指定id的数据
     *
     * @param string $id
     * @return Doggy_Storage_Provider_FileSystem
     */
    public function delete($id){
        $path = $this->root.'/'.$this->_getHashPath($id);
        if(file_exists($path)){
            @unlink($path);
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
        $path = $this->root.'/'.$this->_getHashPath($id);
        if(file_exists($path)){
            return file_get_contents($path);
        }
        return null;
    }
    /**
     * 返回后端指定id的数据的Path Uri以便客户端可以用fopen进行后续操作
     *
     * @param string $id
     * @return string
     */
    public function getPath($id){
        $path = $this->root.'/'.$this->_getHashPath($id);
        if(file_exists($path)){
            return $path;
        }
        return null;
    }
    /**
     * 返回指定id的可直接访问的uri地址（如果可能)
     * 
     * 如果资源不存在或不支持uri访问则返回null
     *
     * @param string $id
     * @return string
     */
    public function getUri($id){
        $path = $this->_getHashPath($id);
        if(file_exists($this->root.'/'.$path)){
            return $this->rootUrl.'/'.$path;
        }
        return null;
    }
    /**
     * 检测是否已经存在指定id的数据
     *
     * @param string $id
     * @return boolean
     */
    public function exists($id){
        $path = $this->root.'/'.$this->_getHashPath($id);
        return file_exists($path);
    }
    /**
     * 复制指定id的数据到新的id,若新的id已经存在则将被覆盖
     *
     * 注意：复制是无条件复制,因此客户端应自行检查是否存在目标id
     *
     * @param string $id
     * @param string $copyId
     * @return Doggy_Storage_Provider_FileSystem
     */
    public function copy($id,$copyId){

        if($id==$copyId){
            Doggy_Log_Helper::warn('found self copy operation!'.$id);
            return $this;
        }

        $data = $this->get($id);
        if(!is_null($data)){
            $this->store($copyId,$data);
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
     * @return Doggy_Storage_Provider_FileSystem
     */
    public function rename($oldId,$newId){

        $oldPath = $this->root.'/'.$this->_getHashPath($oldId,false);
        if(!file_exists($oldPath)){
            Doggy_Log_Helper::warn('id:'.$oldId.' isnot exists!',__METHOD__);
            throw new Doggy_Storage_Exception('id:'.$oldId.' isnot exists!');
        }

        if($oldId == $newId){
            Doggy_Log_Helper::warn('found self rename operation!'.$oldId,__METHOD__);
            return $this;
        }

        $newPath = $this->root.'/'.$this->_getHashPath($newId);
        $ok=rename($oldPath,$newPath);
        if(!$ok){
            Doggy_Log_Helper::error('Cannot rename file from :'.$oldPath.' to '.$newPath,__METHOD__);
            throw new Doggy_Storage_Exception('rename resource from '.$oldId.' to '.$newId.' failed');
        }
        return $this;
    }
}
/**vim:sw=4 et ts=4 **/
?>