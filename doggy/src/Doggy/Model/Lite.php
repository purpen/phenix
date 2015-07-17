<?php
/**
 * Base class for  domain entity model
 * 
 * This is a lite  ActiveRecord of Doggy 1.2.x
 * 
 * 
 * NOTE: Most relations operation were removed, cause's them is hard to learn, and bad optimize possible.
 * 
 * @author night
 */
class Doggy_Model_Lite {
    
     /**
      * Flag current data is saved
      * 
      * @var boolean
      */
    protected $saved = false;
    

     /**
      * Primary key
      * 
      * @var mixed
      */
    protected $pk = 'id';
    
    /**
     * Current model row data to save
     * 
     * @var array
     */
    protected $data = array();

    /**
     * Model对应的表的前缀
     * @var string
     */
    protected $table_prefix='';
    
    /**
     * Real table name in database
     *
     * @var string
     */
    protected $db_table_name;
    
    /**
     * Model对应的Table名
     * @var string
     */
    protected $model_name;

    /**
     * Model 类名
     * @var string
     */
    protected $model_class;
    
    
    /**
     * ActiveRecord的属性数组
     *
     * @var array
     */
    protected $fields = null;
    
    /**
     * Model使用的DB
     * 
     * @var Doggy_Db_Driver
     */
    protected static $_db=null;    
    
    protected static $_map = null;
    
    protected $_validate_errors = array();
    
    protected  $disable_identify_map = false;
    /**
     * Allow auto set timestamp
     *
     * @var string
     */
    protected $auto_update_timestamp = true;
    
    /**
     * Auto timestamp fields list when create record
     *
     * @var array
     */
    protected $created_timestamp_fields = array('created_on');
    /**
     * Auto timestamp fields list when update record
     *
     * @var array
     */
    protected $updated_timestamp_fields = array('updated_on');
    
    /**
     * Fields default value
     *
     * @var string
     */
    protected $defaults = array();
    
    /**
     * Constructur
     */
    public function __construct(){
        
        if (is_null($this->model_class)) {
            $this->model_class = get_class($this);
        }
        
        if (is_null(self::$_db)) {
            self::$_db = Doggy_Model_DbHelper::get_model_db($this->model_name);
        }
        
        $this->db_table_name = (empty($this->table_prefix)?'':$this->table_prefix.'_').$this->model_name;
        
        $meta_key = 'app.model.meta.'.$this->model_name;
        
        if (isset(Doggy_Config::$vars[$meta_key])) {
            $this->fields = & Doggy_Config::$vars[$meta_key];
        }
        else {
            $this->load_fields_from_db();
        }
        
        if (!$this->disable_identify_map && !isset(self::$_map)) {
            $map = Doggy_Model_IdentifyMap::get_map($this->model_name);
            if ($map) {
                self::$_map = $map;
            }
        }
        
    }
        
    public function table_prefix() {
        if (func_num_args()) {
            $this->table_prefix = func_get_arg(0);
            $this->db_table_name = (empty($this->table_prefix)?'':$this->table_prefix.'_').$this->model_name;
        }
        return $this->table_prefix;
    }
    
    public function model_name() {
        return $this->model_name;
    }
    
    public function db_table_name() {
        return $this->db_table_name;
    }
    
    public function model_class() {
        return $this->model_class;
    }
    
    public function __set($name,$value) {
        if (isset($this->fields[$name])) {
            $this->data[$name] = $value;
        }
    }
    
    public function __get($name) {
        if(isset($this->data[$name])) {
            return $this->data[$name];
        }else{
            return null;
        }
    }
    
    //~~~{{{ private method
    /**
     * 根据model的属性值在数据库表中添加相应的记录
     * 
     * @access  private
     */
    private function _insert_db_record(){
        try{
            $vars = array();
            $sql = Doggy_Model_SqlBuilder::build_create_sql($this->db_table_name,$this->fields,$this->data,$vars);
            
            self::$_db->execute($sql,$vars);
            $this->saved = true;
            
        }catch(Doggy_Db_Exception $e){
            Doggy_Log_Helper::error("Create cecord failed,DB Error:".$e->getMessage(),__METHOD__);
            throw new Doggy_Model_Exception('Create record error:'.$e->getMessage());
        }
     }
     
    /**
     * 根据model的属性值更新数据库表中相应的记录
     * @return bool
     * @access private
     */
    private function _update_db_record(){
        
        $vars = array();
        $sql = Doggy_Model_SqlBuilder::build_update_sql($this->db_table_name,$this->fields,$this->data,$this->pk,$vars);
        try{
            self::$_db->execute($sql,$vars);
        }catch(Doggy_Dba_Exception $e){
            Doggy_Log_Helper::error("Update record failed,DBA error:".$e->getMessage());
            throw new Doggy_Model_Exception("Update record failed,DBA error:".$e->getMessage());
        }
    }
     
    /**
     * Load fields(attributes) meta data from database
     *
     * @return boolean
     */
    private function load_fields_from_db(){
        $meta_key = 'app.model.meta.'.$this->model_name;
        try{
            $fields = self::$_db->get_fields($this->db_table_name);
            Doggy_Config::set($meta_key,$fields);
        }catch(Doggy_Db_Exception $e){
            Doggy_Log_Helper::error("Error while fetch table $table fields:".$e->getMessage());
            return false;
        }
        $this->fields = & Doggy_Config::$vars[$meta_key];
        return true;
    }
    
    
	/**
     * 查找记录，返回全部符合匹配条件的记录
     *
     * 本方法是支持各种find的核心操作。
     * <p>
     * 可以传递一个关联数组$options，用来说明查询的条件，options支持的选项key有:
     * 
     * condition: string,SQL查询的Where条件语句(不包括WHERE关键字)
     * order_by: string,SQL的order语句，如 created_time DESC,name ASC,age ASC
     * size: 一个整数，表示要分页时每页的记录数,-1表示不分页，返回全部
     * page: 一个整数，表示返回页的索引号，如果设置了limit，则此参数默认为1
     * fields: string,默认情况下,select * FROM table,如果你希望用具体的字段限定来替换*,那么可以指定字段列表,如 'name,age'
     * joins: string,SQL查询时需要附加的JOINS语句，比如"LEFT JOIN comments ON comments.post_id = id"
     * group_by:string SQL GROUPBY条件
     * vars: array,要传递的预编译参数数组，如果sql中使用了?这些占位符
     * 
     * @param array $options
     * @return array
     * 
     */
    public function find($options=array()){
        $options['table'] = isset($options['table'])?$options['table']:$this->db_table_name;
        $sql = Doggy_Model_SqlBuilder::build_sql_options($options);
        return $this->find_by_sql($sql,$options);
    }
    
    /**
     * Find fist rows by primary key;
     *
     * @param mixed $pk  pk value
     * @return array
     */
    public function find_by_pk($pk=null){
        if (isset(self::$_map)) {
            $data = self::$_map->load($pk);
            if ($data) {
                if (is_array($pk) && count($pk) > count($data)) {
                    $missing_keys = array_diff($pk,array_keys($data));
                    $missing_data = $this->find_by_pk($missing_keys);
                    return $data+$missing_data;
                }
                else{
                    return $data;
                }
            }
        }
        $options['condition'] = Doggy_Model_SqlBuilder::build_pk_where($this->pk,$pk);
        if (is_array($pk)) {
            $options['vars'] = $pk;
            $rows = $this->find($options);
            $result = array();
            foreach ($rows as $row) {
                $result[$row[$this->pk]] = $row;
            }
            #reload this missing data into map
            if (self::$_map) {
                foreach ($rows as $row ) {
                    self::$_map->add($row[$this->pk],$row);
                }
            }
            return $result;
        }
        else {
            $options['vars'] = array($pk);
            $data =  $this->first($options);
            if (!empty($data) && self::$_map) {
                self::$_map->add($pk,$data);
            }
            return $data;
        }
    }
    
    private function _index_by_pk($rows) {
        $result = array();
        foreach ($rows as $row) {
            $result[$row[$this->pk]] = $row;
        }
        return $result;
    }
    
    /**
     * Alias of find_by_pk
     *
     * @param string $pk 
     * @param string $options 
     * @return array
     */
    public function find_by_id($pk=null,$options=array()) {
        return $this->find_by_pk($pk,$options);
    }
    
    /**
     * Load single row from db
     *
     * @param string $pk 
     * @return void
     */    
    public function load($pk) {
        $result = $this->find_by_pk($pk);
        $this->saved = true;
        $this->data = $result;
    }
    
    
    /**
     * 查找并返回匹配的第一条记录
     *
     * @param array $options condition or other options to find
     * @return array
     */
    public function first($options=array()){
        $options['size']=1;
        $options['page']=1;
        $options['_first']=true;
        return $this->find($options);
    }
    
    /**
     *
     * 通过直接指定SQL查找匹配的记录
     *
     * 这是一个底层的SQL查找方法,需要指定一个完整的sql语句，同时可以使用bindingVars。
     *
     * @param string $sql 执行查询的完整的SQL语句
     * @param array $options
     * @return array
     */
    public function find_by_sql($sql,$options=array()){
        $size=-1;
        $page=1;
        $vars=null;
        $readonly=false;
        $_first=false;
        extract($options,EXTR_IF_EXISTS);
        try{
            $result = self::$_db->query($sql,$size,$page,$vars);
            //hacking for findFirst,is it right?
            if($_first){
                $result = empty($result)?array():$result[0];
                $this->after_find($result);
                return $result;
            }else{
                $this->after_find($result);
                return $result;
            }
        }catch(Doggy_Db_Exception $e){
            Doggy_Log_Helper::error("Db Error:".$e->getMessage());
            throw new Doggy_Model_Exception("Find data failed:".$e->getMessage());
        }
    }
  
    /**
     * 创建一个待添加保存的新记录,可选的data是新记录集的数据
     *
     * @param string $data 
     * @return void
     */ 
    public function insert($data = array()){
        $this->data= $data + $this->defaults;
        $this->saved = false;
    }
    
    
    /**
     * Create and save new record
     *
     * @param array $data 
     * @return bool
     */
    public function create($data) {
        $this->insert();
        return $this->save($data);
    }
    
    /**
     * Returns current timestamp. this value will apply to
     * auto-timestamp fields.
     *
     * @return string
     */
    protected function current_timestamp() {
        return date('Y-m-d H:i:s');
    }
    
    protected function now() {
        return date('Y-m-d H:i:s');
    }
    /**
     * 保存当前model
     * 
     * 保存包括插入和更新，根据$this->saved来确定是插入还是更新。
     * 
     * 保存的过程如下:
     * - before_validation
     * - validate
     * - after_validation
     * - before_save
     * 如果是新记录，则
     * 	- before_create
     * 		- 新建记录到数据库
     * 	－ after_create
     * 否则：
     * 	- before_update
     * 	- 更新记录到数据库
     *  - after_update
     * - afterSave
     *
     * @param array $data optional
     * @param bool $validate optional whether validate
     * @return  Doggy_Model_Base
     * 
     * @throws Doggy_Model_ValidateException
     * @throws Doggy_Model_Exception
     */
    final public function save($data=null,$validate = true){
        
        if(!empty($data)){
            $this->data = array_merge($this->data,$data);
        }
        
        //auto generate id if primary key is single field
        if (is_null($this->pk())) {
            if(!$this->saved) {
                $this->pk(Doggy_Sequence_Generator::next_id($this->model_name));
            }
        }
        //auto update timestamp
        if ($this->auto_update_timestamp) {
            if (!$this->saved) {
                foreach ($this->created_timestamp_fields as $field ) {
                    if (!isset($this->data[$field])) {
                        $this->data[$field] = $this->current_timestamp();
                    }
                }
            }
            else {
                foreach ($this->updated_timestamp_fields as $field ) {
                    if (!isset($this->data[$field])) {
                        $this->data[$field] = $this->current_timestamp();
                    }
                }
            }
        }
        
        $this->before_validation();
        
        if($validate){
            if(!$this->validate()){
                throw new Doggy_Model_ValidateException('validate faield.');
            }
        }
        $this->after_validation();
        
        $this->before_save();
        
        if(!$this->saved){
            //this is new record
            $this->before_create();
            try{
                $this->_insert_db_record();
            }catch(Doggy_Model_Exception $e){
                $this->on_create_error($e);
                $this->on_save_error($e);
                throw $e;
            }
            $this->after_create();
        }else{
            //here is update mode
            $this->before_update();
            try{
                $this->_update_db_record();
            }catch(Doggy_Model_Exception $e){
                $this->on_update_error($e);
                $this->on_save_error($e);
                throw $e;
            }
            $this->after_update();
        }
        $this->after_save();
        
        //update identify map
        if (isset(self::$_map)) {
            self::$_map->put($this->pk(),$this->data);
        }
        
        return $this;
    }


    /**
     * destroy方法的别名
     */
    public function remove($id=null){
        return $this->destroy($id);
    }
    /**
     * 创建指定id的ActiveRecord对象，并调用其destroy方法(对象的callback将被触发)
     *
     * @param mixed $id id数组或者单个id,空则删除自身
     * @return boolean
     */
    final public function destroy($id=null){
        if(is_array($id)){
            return $this->destroy_all(Doggy_Model_SqlBuilder::build_pk_where($this->pk,$id),$id);
        }
        $id = is_null($id)?$this->pk():$id;
        //here, actually destroy work
        $data = $this->find_by_pk($id);
        $this->before_destroy($data);
        $this->delete($id);
        $this->after_destroy($data);
        
        //map
        if (isset(self::$_map)) {
            self::$_map->remove($id);
        }
        
        return $this;
    }
   
    /**
     * 查找出符合条件的对象,并调用其remove方法删除(同时触发events)
     * 
     * @param string $condition
     * @param array $vars bind array
     * @return Doggy_Model_Base
     */
    public function destroy_all($condition,$vars=null){
        $rows = $this->find(array('fields'=>$this->pk,'condition'=>$condition,'vars'=>$vars));
        $success =0;
        $size = count($rows);
        if($size>0){
            for($i=0;$i<$size;$i++){
                $pk = $rows[$i][$this->pk];
                $this->destroy($pk);
            }
        }
        return $this;
    }
    
    /**
     * Apply row data into self
     *
     * @param array $data 
     * @return void
     */
    /**
     * Apply row data into current dataset
     *
     * @param string $data 
     * @param bool $purge if true will clear current dataset 
     * @return void
     */
    public function apply($data,$purge = false) {
        
        if ($purge) {
            $this->data = array();
        }
        
        foreach ($data as $key => $value) {
            $this->__set($key,$value);
        }
        
        if (isset($this->data[$this->pk])) {
            $this->saved = true;
        }
        
    }
    
    /**
     * Current row is saved.
     *
     * @return bool
     */
    public function is_saved() {
        return $this->saved;
    }

    /**
     * 立即从数据库中删除指定id的记录而不先创建对象(该对象的callback将不被触发)
     * 
     * @param mixed $id
     * @return bool
     */
    public function delete($id){
        return $this->delete_all(Doggy_Model_SqlBuilder::build_pk_where($this->pk,$id),array($id));
    }
    
    /**
     * 立即从数据库中删除符合条件的记录而不先创建ActiveRecord对象(callback将不被触发)
     *
     * @param string $condition
     * @param array $vars
     */
    public function delete_all($condition=null,$vars=array()){
        $sql = "DELETE  FROM ". $this->db_table_name;
        if(!empty($condition)){
            $sql .= " WHERE $condition";
        }
        return self::$_db->execute($sql,$vars);
    }
        
    /**
     * 设置ActiveRecord使用的DBA实例
     *
     * @param  Doggy_Db_Driver $value
     * 
     */
    final public static function set_db($db){
        self::$_db = $db;
    }

    /**
     * 查找匹配指定条件的记录的数量，如果没有匹配则返回0
     * @param string $condition
     * @param array $vars
     * @return int
     */
    public function count_if($condition=null,$vars=null){
        $sql = 'SELECT COUNT(*) AS cnt FROM '.$this->db_table_name;
        if(!empty($condition)){
            $sql.=" WHERE $condition ";
        }
        $row = self::$_db->query($sql,1,1,$vars);
        return $row[0]['cnt'];
    }
    
    /**
     * 数据库中是否存在指定id的model对象
     *
     * @param mixed $id array of int or int
     * @return boolean True if anyone exists, False otherwise.
     */
    public function has($id){
        $condition = Doggy_Model_SqlBuilder::build_pk_where($this->pk,$id);
        $vars = is_array($id)?$id:array($id);
        $count = $this->count_if($condition,$vars);
        
        return (boolean)($count>0);
    }

    /**
     * 数据库中是否存在符合条件的记录
     *
     * @param string $condition where clause
     * @param array $vars bind array
     * @return bool
     */
    public function has_if($condition,$vars=null){
        $count = $this->count_if($condition,$vars);
        return (boolean)($count>0);
    }
    
    
    //*************************************************************
    //
    //----------------各类Accessor 方法-----------
    //
    
    /**
     * Set or get model's primary key value
     *
     * @return mixed
     */
    public function pk() {
        if (func_num_args()) {
            $this->data[$this->pk] = func_get_arg(0);
        }
        return isset($this->data[$this->pk]) ? $this->data[$this->pk]:null;
    }
    
    /**
     * Returns current model's internal identify map object
     *
     * @return Doggy_Model_Identify_Map
     */
    public function get_map() {
        return self::$_map;
    }

    //*************************************************************
    //
    //----------------可重载的callbacks事件-----------
    //
    /**
     * 这里定义支持的callback事件比RoR的ActiveRecord要精简很多
     *
     * 以下是各个事件的触发时序表:
     *
     * create时：
     *     - before_validation
     *     - validate
     *     - after_validataion
     *     - before_save
     *     - before_create
     *     - after_create
     *     - after_save
     *
     * update时:
     *     - before_validation
     *     - validate
     *     - after_validation
     *     - before_save
     *     - after_update
     *     - before_update
     *     - after_save
     *
     * destroy:
     *     - before_destroy
     *     - after_destroy
     *
     * find:
     *    after_find
     */

    /**
     * before_create callback
     * @abstract
     * @return bool
     */
    protected function before_create(){return true;}
    /**
     * after_create callback
     * @abstract
     * @return bool
     */
    protected function after_create(){return true;}

    /**
     * before_save callback
     * @abstract
     * @return bool
     */
    protected function before_save(){return true;}
    /**
     * after_save callback
     * @abstract
     * @return bool
     */
    protected function after_save(){return true;}
    /**
     * before_update callback
     * @abstract
     * @return bool
     */
    protected function before_update(){return true;}

    /**
     * after_update callback
     * @abstract
     * @return bool
     */
    protected function after_update(){return true;}

    /**
     * before_validation callback
     * @abstract
     * @return bool
     */
    protected function before_validation(){return true;}
    /**
     * after_validation callback
     * @abstract
     * @return bool
     */
    protected function after_validation(){return true;}

    /**
     * 校验数据有效性方法,用户应重载以便实现数据检查
     * @abstract
     * @return bool
     * @throw Doggy_Model_ValidateException
     */
    protected function validate(){return true;}

    /**
     * before_destory callback
     * @param array $ori_data 
     * @return bool
     */
    protected function before_destroy($ori_data){}
    /**
     * after_destroy callback
     * @return bool
     */
    protected function after_destroy($ori_data){}
    /**
     * callback after find got result but before return it
     */
    protected function after_find(&$rows){}
    
    /**
     * 保存失败的时候回调
     * 
     * @param Doggy_Model_Exception $e
     * @return boolean 是否要抑制异常,true则不向上抛出异常
     */
    protected function on_save_error($e){}
    /**
     * 创建失败的时候回调
     * 
     * @param Doggy_Model_Exception $e
     */
    protected function on_create_error($e){}
    
	/**
	 * 更新失败的时候回调
	 * 
     * @param  Doggy_Model_Exception $e
     */
    protected function on_update_error($e){}
     
}
/** vim: sw=4 expandtab ts=4 : **/
?>