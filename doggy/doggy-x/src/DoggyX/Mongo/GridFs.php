<?php
/**
 * 增强版的MongoGridFS
 * 
 * - 包装GridFS
 * - 减少重复文件存储
 * 
 */
class DoggyX_Mongo_GridFs {
    /**
     * 存储文件的属性
     *
     * @var array
     */
    protected $schema = array(
        //文件md5 hash
        'md5' => 0,
        //引用计数, 当计数器变为0时会被自动清理
        'refs' => 1,
        );
        
    protected static $gridfs;
    
    public function __construct() {
        if (is_null(self::$gridfs)) {
            $db = DoggyX_Mongo_Manager::get_db('gridfs');
            self::$gridfs = $db->get_fs();
        }
    }
    
    /**
     * 手动设置要使用的MongoGridFS
     *
     * @param MongoGridFS $gridfs 
     * @return void
     */
    public static function set_gridfs($gridfs) {
        self::$gridfs = $gridfs;
    }
    
    /**
     * 保存文件到后端gridfs,返回保存的MongoId
     *
     * @param string $file 
     * @return MongoId
     */
    public function store_file($file) {
        if (!is_file($file)) {
            throw new DoggyX_Mongo_Exception("file $file not exits!");
        }
        $md5 = md5_file($file);
        $fh = self::$gridfs->findOne(array('md5' => $md5));
        if (empty($fh)) {
            $attrs = $this->schema;
            $attrs['md5'] = $md5;
            $id = self::$gridfs->storeFile($file,$attrs);
        }
        else {
            self::$gridfs->update(array('_id' => $fh->file['_id']),array('$inc' => array('refs' => 1)));
            $id = $fh->file['_id'];
        }
        return $id;
    }
    
    /**
     * 保存数据到后端gridfs,返回保存的MongoId
     *
     * @param string $bytes 
     * @return MongoId
     */
    public function store_bytes($bytes) {
        $md5 = md5($bytes);
        $fh = self::$gridfs->findOne(array('md5' => $md5));
        if (empty($fh)) {
            $attrs = $this->schema;
            $attrs['md5'] = $md5;
            $id = self::$gridfs->storeBytes($bytes,$attrs);
        }
        else {
            self::$gridfs->update(array('_id' => $fh->file['_id']),array('$inc' => array('refs' => 1)));
            $id = $fh->file['_id'];
        }
        return $id;
    }
    
    protected function inc_refs($_id) {
        self::$gridfs->update(array('_id' => $_id),array('$inc' => array('refs' => 1)));
    }
    
    protected function dec_refs($_id) {
        self::$gridfs->update(array('_id' => $_id),array('$inc' => array('refs' => -1)));
    }
    
    /**
     * 删除一个文件(指定id)
     *
     * @param string $file_id 
     * @return void
     */
    public function unlink($file_id) {
        $this->dec_refs(DoggyX_Mongo_Db::id($file_id));
    }
    
    public function count() {
        return self::$gridfs->count();
    }
    
    /**
     * 清除不再被引用的孤立文件
     *
     * @return void
     */
    public function gc() {
        self::$gridfs->remove(array('refs' => 0));
    }
    
    /**
     * 获取指定的文件Id的MongoGridFSFile
     *
     * @param string $file_id 
     * @return MongoGridFSFile
     */
    public function get_file($file_id) {
        return self::$gridfs->findOne(array('_id' => DoggyX_Mongo_Db::id($file_id) ));
    }
    
    public function drop() {
        return self::$gridfs->drop();
    }
}