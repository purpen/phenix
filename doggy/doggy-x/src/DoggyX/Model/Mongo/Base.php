<?php
/**
 * MongoDb Model基础类(改自Doggy_Model_MongoLite).
 * Todo:
 *  - Handle mongodb errors, throws exception.
 *  - Fetch update/insert information, triggle events
 *  - More features backport from  mysql based "Doggy_Model_Lite"
 */
class DoggyX_Model_Mongo_Base {

    const MONGO_ID_NAME = '_id';
    const MONGO_ID_NATIVE = 1;
    const MONGO_ID_SEQ = 2;
    const MONGO_ID_CUSTOM=3;
    const MONGO_SEQ_COLLECTION = 'sequence';
    
    protected $force_schema_free = false;
    protected static $_db;
    protected $collection = 'test';
    protected $seq_name;
    
    /**
     * 默认属性
     *
     * @var array
     */
    protected $schema = array();
    
    protected $data = array();
    
    
    protected $mongo_id_style = self::MONGO_ID_NATIVE;

    protected $auto_update_timestamp = true;
    protected $created_timestamp_fields = array('created_on','updated_on');
    protected $updated_timestamp_fields = array('updated_on');
    
    protected $insert_mode = false;
    
    /**
     * 强制必填字段
     *
     * @var array
     */
    protected $required_fields = array();

    /**
     * Int 类型属性, 在save 之前将自动将这些属性转换为int
     *
     * @var array
     */
    protected $int_fields = array();
    
    /**
     * Float 类型属性, 在save 之前将自动将这些属性转换为float
     *
     * @var array
     */
	protected $float_fields = array();

    public function __construct() {
        if (empty($this->collection)) {
            throw new Doggy_Model_Exception('Invalid Model Class:'.__CLASS__.' => collection undefined!');
        }
        if ($this->mongo_id_style == self::MONGO_ID_SEQ && empty($this->seq_name)) {
            $this->seq_name = $this->collection;
        }
        self::$_db = DoggyX_Mongo_Manager::get_model_db($this->collection);
    }
    
    public function __set($name,$value) {
        if ($name == 'id') {
            $name = self::MONGO_ID_NAME;
        }
        if ($name == self::MONGO_ID_NAME && $this->mongo_id_style == self::MONGO_ID_NATIVE) {
            $value = DoggyX_Mongo_Db::id($value);
        }
        //add magic setter
        if (method_exists($this,"set_$name")) {
            $m = "set_$name";
            $this->$m($value);
        }
        else {
            $this->data[$name] = $value;
        }
    }
    
    public function __get($name) {
        if ($name == 'id') {
            $name = self::MONGO_ID_NAME;
        }
        if(isset($this->data[$name])) {
            return $this->data[$name];
        }else{
            //magick attributes!
            if (method_exists($this,$name)) {
                return $this->$name();
            }
        }
    }
    
    public function __isset($name) {
        if ($name == 'id') {
            $name = self::MONGO_ID_NAME;
        }
        return isset($this->data[$name]);
    }
    
    public function reset() {
        $this->data = array();
        $this->insert_mode = true;
    }
    
    
    public function is_saved($data=null) {
        if (is_null($data)) {
            $data = & $this->data;
        }
        return isset( $data[ self::MONGO_ID_NAME ]);
    }
    
    public function load($id) {
        $this->data = $this->find_by_id($id);
        return $this->data;
    }

    public function reload() {
        return $this->load($this->id);
    }

    public function insert(array $data=array()) {
        return $this->create($data);
    }
    
    public function create(array $data=array()) {
        $this->reset();
        $this->data =  $data + $this->schema;
        return $this->save();
    }
    
    public function set_data(array $data=array()) {
        $this->data = $data;
    }
    
    public function get_data() {
        return $this->data;
    }
    
    protected function _build_query($criteria) {
        if (!is_array($criteria) && !empty($criteria)) {
            
            if ($this->mongo_id_style == self::MONGO_ID_NATIVE) {
                $criteria = array( self::MONGO_ID_NAME => DoggyX_Mongo_Db::id($criteria) );
            }
            else {
                $criteria = array( self::MONGO_ID_NAME => $criteria );
            }
        }
        return $criteria;
    }
    
    protected function has_modifier($data) {
        $keys = array_keys($data);
        foreach ($keys as $k) {
            if (is_string($k) && substr($k,0,1) == '$') {
                return true;
            }
        }
        return false;
    }
    
    public function update($criteria,array $new_data,$upsert=false,$multiple=false,$safe = false) {
        if ($this->auto_update_timestamp) {
            $ts = time();
            $has_modifier = $this->has_modifier($new_data);
            foreach ( $this->updated_timestamp_fields as $f ) {
                // if updated data include modifer, we must use $set to update timestamp
                if ($has_modifier) {
                    $new_data['$set'][$f] = $ts;
                }
                else {
                    $new_data[$f] = $ts;
                }
            }
        }
        $this->before_update($new_data);
        $query = $this->_build_query($criteria);
        return self::$_db->update($this->collection,$query,$new_data,$upsert,$multiple,$safe);
    }
    
    public function update_set($criteria,array $some_data,$upsert=false,$multiple=false,$safe=false) {
        return $this->set($criteria,$some_data,$upsert,$multiple,$safe);
    }
    
    public function update_unset($criteria,array $unset_fields) {
        return $this->un_set($criteria,$unset_fields);
    }
    
    public function set($criteria, array $some_data,$upsert=false,$multiple=false,$safe=false) {
        if ($this->auto_update_timestamp) {
            $ts = time();
            foreach ( $this->updated_timestamp_fields as $f ) {
                $some_data[$f] = $ts;
            }
        }
        $query = $this->_build_query($criteria);
        return self::$_db->set($this->collection,$query,$some_data,$upsert,$multiple,$safe);
    }
    
    public function un_set($criteria,array $unset_fields,$multiple=false,$safe=false) {
        if ($this->auto_update_timestamp) {
            $ts = time();
            foreach ( $this->updated_timestamp_fields as $f ) {
                $set[$f] = $ts;
            }
        }
        $query = $this->_build_query($criteria);
        $data['$unset'] = $unset_fields;
        if (!empty($set)) {
           $data['$set'] = $set;
        }
        return self::$_db->update($this->collection,$query,$data,false,$multiple,$safe);
    }
    
    public function inc($criteria,$field,$inc=1,$upsert = true,$multiple = false,$safe = false) {
        $query = $this->_build_query($criteria);
        return self::$_db->inc($this->collection,$query,$field,$inc,$upsert,$multiple,$safe);
    }
    
    public function dec($criteria,$field,$dec=1,$upsert = false, $multiple = false,$safe = false) {
        $query = $this->_build_query($criteria);
        return self::$_db->inc($this->collection,$query,$field,$dec*-1,$upsert,$multiple,$safe);
    }
    
    public function ensure_index(array $keys) {
        return self::$_db->ensure_index($this->collection,$keys);
    }
    
    public function first($query=array(),$fields=array()) {
        return self::$_db->first($this->collection,$query,$fields);
    }
    
    /**
     * Remove a row
     *
     * @param string $criteria 
     * @param mixed $safe boolean or interger.
     * @return void If $safe is true,the program will wait for the database response and throw a
     *  MongoCursorException if the update did not succeed.
     */
    public function remove($criteria=array(),$safe = false) {
        $query = $this->_build_query($criteria);
        return self::$_db->remove($this->collection,$query,$safe);
    }
    
    public function count($criteria=array()) {
        return self::$_db->count($this->collection,$criteria);
    }
    
    public function find(array $query=array(), array $options=array() ) {
        return self::$_db->find($this->collection,$query,$options);
    }
    
    
    public function find_by_id($id,$fields=array()) {
        return self::$_db->first($this->collection, $this->_build_query($id), $fields);
    }
    
    
    public function save(array $data=array()) {
        if (!empty($data)) {
           $this->data = array_merge($this->data,$data);
        }
        if ($this->is_saved()) {
            $this->insert_mode = false;
        }
        else {
            $this->insert_mode = true;
        }
        //auto update timestamp field
        if ($this->auto_update_timestamp) {
            $ts = time();
            if ($this->insert_mode) {
                $fields_list = $this->created_timestamp_fields;
            }
            else {
                $fields_list = $this->updated_timestamp_fields;
            }
            foreach ($fields_list as $f ) {
                $this->data[ $f ] = $ts;
            }
        }
        
        if ($this->insert_mode) {
            $this->before_insert($this->data);
            self::validate_required_fields($this->data, $this->required_fields);
            if ( $this->mongo_id_style == self::MONGO_ID_SEQ ) {
                $this->id = $this->next_seq_id($this->seq_name);
            }
        }
        if ( ! $this->validate() ) {
            throw new Doggy_Model_ValidateException('field invalid.');
            // return false;
        }
        
		// 转换整型数字段
        $this->_cast_int_fields($this->data);
		$this->_cast_float_fields($this->data);
        
        $this->before_save($this->data);
        $ok = self::$_db->save($this->collection,$this->data);
        $this->after_save();
        return $ok;
    }
    
    public function drop($collection = null) {
        if (is_null($collection)) {
            $collection = $this->collection;
        }
        self::$_db->drop($collection);
    }
    
    public function drop_seq_collection() {
        return $this->drop(self::MONGO_SEQ_COLLECTION);
    }
    
    public static function validate_required_fields($data,$fields) {
        if (!is_array($fields)) {
            $fields = array($fields);
        }
        $invalids = array();
        foreach ($fields as $f ) {
            if (empty($data[$f])) {
                $invalids[] = $f;
            }
        }
        if (!empty($invalids)) {
            throw new Doggy_Model_ValidateException('some fields are null:<'.implode($invalids).'>');
        }
        return true;
    }
    
    public function next_seq_id($seq_name) {
        // todo: remove this derepcated code future
        if (defined('MOCK_FIND_AND_MODIFY')) {
            return self::$_db->call_function('next_seq_id',array('seq_name' => $seq_name));
        }
        // note, you must ensure_index on {seq_name:1}
        $val = self::$_db->find_and_modify(self::MONGO_SEQ_COLLECTION,array(
            'query'  => array('name' => $seq_name),
            'update' => array('$inc' => array('val' => 1)),
            'new' => true,
            'upsert' => true,
            ));
        return $val['val'];
    }
    
    public function set_seq_val($seq_name,$val) {
        $query['name'] = $seq_name;
        $row['val'] = $val;
        $row['name'] = $seq_name;
        return self::$_db->set(self::MONGO_SEQ_COLLECTION,$query,$row,true);
    }
    /**
     * Install server side function to generate a sequence value(Auto increment).
     * 
     * Note: This is deprecated!
     * 
     * @return void
     * @deprecated
     */
    public function install_sequence() {
        $code = 'function() {
            db.sequence.update({name:seq_name},{$inc:{val:1}},true);
            var row = db.sequence.findOne({name:seq_name});
            return row.val;
            };';
        return self::$_db->store_server_function('next_seq_id',$code);
    }
    
    public function apply_schema_data(array $data,array $addition_schema_fields=array() ) {
        
        $fields = array_keys($this->schema);
        if (!empty($addition_schema_fields)) {
            $fields = array_merge($fields,$addition_schema_fields);
        }
        //schema free, apply anything!
        if ($this->force_schema_free || empty($fields)) {
            $this->set_data($data);
        }
        
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $this->__set($f,$data[$f]);
            }elseif(isset($this->schema[$f])){ // 添加设置默认值的字段 modify by purpen
				$this->__set($f,$this->schema[$f]);
			}
        }
    }
    
    /**
     * 过滤并返回匹配schema中定义的字段的数组
     * 
     * 当做从action中对传递的数据进行局部更新时使用,避免添加多余的数据
     * 
     * @param array $data 
     * @param array $addition_schema_fields 附加检查的字段,通常是id
     * @param bool $include_id 是否包含MONGOID,当使用$set更新时不能包含
     * @return array
     */
    public function filter_schema_data(array $data,array $addition_schema_fields=array('id'),$include_id=false) {
        $fields = array_keys($this->schema);
        if (!empty($addition_schema_fields)) {
            $fields = array_merge($fields,$addition_schema_fields);
        }
        $result = array();
        foreach ($fields as $f) {
            if (isset($data[$f])) {
                if ($f == 'id') {
                    $result[self::MONGO_ID_NAME] = $data[$f];
                }
                else {
                    $result[$f] = $data[$f];
                }
            }
        }
        if (!$include_id) {
            unset($result[self::MONGO_ID_NAME]);
        }
        return $result;
    }
    
    public function apply_and_save(array $data,array $addition_schema_fields=array('id') ) {
        $this->data += $this->schema;
        $this->apply_schema_data($data,$addition_schema_fields);
        return $this->save();
    }
    
    public function apply_and_update(array $data,array $addition_schema_fields=array('id') ) {
        $id = isset($data[self::MONGO_ID_NAME]) ? $data[self::MONGO_ID_NAME] : (isset($data['id'])?$data['id']:null);
        if ($id === null) {
            return false;
        }
        
		if ( $this->mongo_id_style == self::MONGO_ID_SEQ ) {
			$data[self::MONGO_ID_NAME] = $id = (int)$id;
		}
        
        $data = $this->filter_schema_data($data,$addition_schema_fields);
        $this->_cast_int_fields($data);
        $this->_cast_float_fields($data);
        $this->before_update($data);
        $this->before_save($data);
        return $this->update_set($id,$data);
    }
    
	/**
     * 创建后调用 after_insert
     */
    public function apply_and_insert( array $data, array $addition_schema_fields=array('id') ) {
      $ok = $this->apply_and_save($data, $addition_schema_fields);
      if($ok){
        $this->after_insert();
      }
      return $ok;
    }
	
	/**
     * 更新后调用 after_update
     */
    public function apply_and_update_alias( array $data, array $addition_schema_fields=array('id') ) {
      $ok = $this->apply_and_update($data, $addition_schema_fields);
      if($ok){
        $this->after_update();
      }
      return $ok;
    }
    
    public function execute($code,array $args=array()) {
        return self::$_db->execute($code,$args);
    }
    
    /**
	 * 新建数据之前，补充数据
	 */ 
    protected function before_insert(&$data) {
        
    }
    
    protected function before_save(&$data) {
    }

    protected function before_update(&$data) {
    }
    
    //some hooks
    protected function validate() {
        return true;
    }
    
    protected function after_save() {
    }

	/**
	 * 转换整型数值
	 */
    protected function _cast_int_fields(&$data) {
        foreach ($this->int_fields as $f) {
            if (isset($data[$f])) {
                $data[$f] = (int) $data[$f];
            }
        }
    }
	
	/**
	 * 转换浮点型数值
	 * 统一保留小数点后2位
	 */
	protected function _cast_float_fields(&$data) {
		foreach ($this->float_fields as $f) {
			if (isset($data[$f])){
				$data[$f] = (float)sprintf("%01.2f", $data[$f]);
			}
		}
	}
    
}