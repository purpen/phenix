<?php
/**
 * ActiveRecord 数据对象
 *
 * Model基础类 精简实现R&R中的activer_record模式,已实现:
 * find*
 * save/update
 * callbacks
 * assocations自动保存(支持1-1 1-* *-1 *-*)
 *
 * Doggy_ActiveRecord_Base 实现了类似RoR的ActiveRecord模式.
 * 
 * 主要特性:
 * 
 * 1.数据集
 * 
 * ActiveRecord保存着2种数据集，分别是实例数据集和Find结果数据集
 * 
 * - 实例数据
 * 当执行save,destory 方法时,使用的是实例数据。
 * set(),setId()这类方法影响的也是实例数据。
 * 
 * 
 * - Find结果数据
 * 
 * 当使用find*时，ActiveRecord会将这些查找的结果存入独立的数据和从而和实例数据分开。
 * 相应的，使用Find结果数据的方式也更为简单，
 * 你可以像使用数组一样引用这些数据,此时ActiveRecord对象表现的和数组一样,你可以把ActiveRecord
 * 对象用于任何可以使用数组的地方,包括foreach.
 * 
 * 注意，即使没有调用过任何一个find*函数，依然可以用数组方式获取这些数据，只是都是空数组而已。
 * 
 * 例子:
 * $model = new Doggy_ActiveRecord_Base();
 * //下面这些方法影响的是实例数据
 * $model->set('name','A');
 * $model->set('id',5);
 * 
 * //数组方式引用的是Find结果数据
 * 
 * echo $model[0];
 * echo "count:".count($model);
 * 
 * 2.Chain操作特性
 * ActiveRecord的多数方法都返回对象自身,包括set*这些应该void的方法。最典型的是find*函数，这些都返回
 * 对象自身,因此你即可以象数组一样那样引用数据，也可以同时执行CRUD操作,比如下面的例子:
 * 
 * $model = new Doggy_ActiveRecord_Base();
 * $model->setId(5)->findById();
 * $model->setAge($model['age']+1);
 * $model->save();
 * 
 * 利用chain特性可以让编码更加简单和清爽。
 * 
 * 
 * /////////////////////////////////////////////////////////////
 * - v1.0
 * 重构关系映射
 * 支持关系延迟加载
 * 重构实现SPL的接口
 * - 0.x
 * port from eps
 * PHP5 native implement
 * /////////////////////////////////////////////////////////////
 * 
 * 注意：1.0不再保持向后兼容，如果需要兼容1.0之前的0.x版本，请使用Doggy_ActiveRecord_Compat
 * 
 * @package Doggy
 * @subpackage  ActiveRecord
 * @author night
 * @implements  ArrayAccess, Iterator, Countable,Iterator
 * @see         SPL Documentation For Interface Declarations.
 * @version $Id: Base.php 14717 2008-06-19 08:11:13Z night $
 */
class Doggy_ActiveRecord_Base  implements ArrayAccess,Countable,Iterator{
    
    /**
     * 关联类型定义
     */
    const HAS_ONE='has_one';
    const HAS_MANY='has_many';
    const BELONGS_TO='belongs_to';
    const HAS_AND_BELONGS_TO_MANY='has_and_belongs_to_many';

    //~~~~{{{ private
    
    /**
     * validate错误数组
     */
    private $_validateErrors=array();
    
     /**
      * 当前是否为新记录，从未被保存到数据库中
      * 
      * @var boolean
      * @access private
      */
    private $_new = true;

     /**
      * 不论Model的primaryKey是否是Id，都可以使用id属性来获得这个值
      * 
      * @var mixed
      */
    private $id = null;    
    
    /**
     * 需要回滚的models
     */
    private $_rollback_models=array();
    
    
    //~~~~~{{{ protected 
    
    /**
     * 存放model当前属性的数据集
     * @var array
     */
    protected $_data = array();
    //hold for find* raw data
    protected $_result_data=array();
    /**
     * 存放find*方法的结果数据集
     * @var ArrayObject
     */
    protected $_result;
   
    protected $_rowMode=false;

    /**
     * Model对应的表的前缀
     * @var string
     */
    protected $tableNamePrefix='';
    /**
     * Model对应的表的后缀
     * @var string
     */
    protected $tableNameSuffix='';
    
    /**
     * Model对应的Table名
     * @var string
     */
    protected $tableName;
    
    
    /**
     * Model 类名
     * @var string
     */
    protected $className;
    /**
     * Model表的主键名
     * @var string
     */
    protected $primaryKey = 'id';
    
    /**
     * 用于生成ID的Sequence Name
     *
     * @var string
     */
    protected $sequenceName;
    /**
     * ActiveRecord的属性数组
     *
     * @var array
     */
    protected $_attributes;
    protected $_table;

    
    /**
     * Model使用的DBA对象
     * @var Doggy_Dba_Adapter
     * @access protected
     */
    protected static $_dba=null;    
    
    /**
     * 定义需要自动删除关系类型
     * @var array
     */
    protected $AutoDeleteRelationType = array(
        self::HAS_MANY,
        self::HAS_ONE,
        self::HAS_AND_BELONGS_TO_MANY
    );
    
    /**
     * 定义需要自动保存的关系类型
     * @var array
     */
    protected $AutoSaveRelationType = array(
        self::HAS_MANY,
        self::HAS_ONE,
        self::HAS_AND_BELONGS_TO_MANY,
        self::BELONGS_TO
    );
    
    /**
     * Model关系映射表
     * 
     * 本数组用于定义对象的关系,继承类通过定义此数组来实现各个model之间的关系映射(ORM)。
     * 
     * 映射表的格式如下:
     * protected $RelationMap= array(
     * 	//key为关系的名称
     * 	'relation_key' =>array(
     * 
     * 		//关系类型,分别是:HAS_MANY,HAS_ONE,BELONGS_TO,HAS_AND_BELONGS_TO_MANY
     * 		'type'=>self::HAS_MANY,
     * 
     * 		//要关联的其他model的类名
     *  	'class'=>'', 
     * 		//外键名,如果省略，则按照如下规则设置外键名:
 	 * 		//	hashOne/hasMany: 当前model的表名.'_id'
	 * 		//	belongsTo: 关联的Model的表名_id
 	 * 		'foreign_key'=>null,
 	 * 
 	 * 		//多对多表关联时,中间关联表的表名,
 	 * 		//默认是调用$this->getJoinTable方法来获得(按照2个表的字母顺序获得)				   
 	 * 		join_table'=>null, 
 	 * 
 	 * 		//多对多关系时使用，当前model在中间关联表中的外键名
 	 * 		'this_foreign_key'=>null, 
 	 * 
 	 * 		//多对多关系时使用,关联model在中间关联表中的外键名
 	 * 		'other_foreign_key'=>null,
 	 * 		//多对多关系时使用，关联表中需要附加的字段名列表
 	 * 		//通常关联表中只有2个外键字段，如果存在其他字段可在此指定
 	 * 		'habm_other_fields'=>array(),
 	 * 
 	 * 		//是否和当前model存在依赖关系，如果是则当前model删除后级联删除这个关联
 	 * 		'depend'=>true,
     * 
     * 		//关系映射选项
     *      ///////////////////////////////////////
     * 			//下面的选项和find函数的相同,如果你设置了这些参数，那么将应用
     * 			//这些参数作为默认值来查找关联model的数据
     * 			//////////////////////////////////////
     *  	'options' => array(
     * 			
     * 			//附加的SQL查询的Where条件语句(不包括WHERE关键字)
     * 			//在查找关联数据时，除了基本的关联条件外,还将附加这个条件
	 * 			'condition'=>null,
	 * 			//关联数据的排序,SQL的order语句，如 created_time DESC,name ASC,age ASC
     * 			'order'=>null,
     * 			//一个整数，表示要分页时每页的记录数,-1表示不分页，返回全部关联数据
     * 			'size'=>-1,
     * 			//一个整数，表示返回页的索引号，如果设置了limit，则此参数默认为1
     * 			'page'=>1,
     * 			//默认情况下,select * FROM table,如果你希望用具体的字段限定来替换*,那么可以指定字段列表,如 'name,age'
     * 			'select'=>null,
     * 			//SQL查询时需要附加的JOINS语句，比如"LEFT JOIN comments ON comments.post_id = id"
     * 			'joins'=>null,
     * 			//SQL GROUPBY条件
     * 			'groupby'=null,
     * 			//要传递的预编译参数数组，如果sql中使用了?这些占位符     
     * 			vars=>array()			
     * 		)	
     * 	)
     * )
     * 
     * @since 1.0
     * @var array
     */
    protected $RelationMap=array();

    /**
     * 是否启用延迟加载$RelationMap中的关联数据
     * 如果启用，那么当访问find result数据时，会自动加载关联的数据
     */
    protected $LazyLoadRelation=true;
    
    /**
     * 需要保存的关联model的数组
     * 
     * @var array
     */
    protected $_relationModels=array();
    
    /**
     * 是否启用magick字段
     * 如果启用，那么当访问find result数据时，会自动调用
     * _magic_field方法来
     */
    protected $LazyLoadMagicField=true;
    
    /**
     * 默认的magic field
     * 格式:
     * 
     * protected $MagicField = array(
     * 	'field_name'=>'method'
     * );
     */
    protected $MagicField = array();
    
    static private $_enable_internal_cache = true;
    
    /**
     * 创建一个ActiveRecord对象
     *
     * @param array $data optional 裸数据
     */
    public function __construct($data=null){
        
        if(is_null($this->className)){
            $this->className = get_class($this);
        }
        
        if($this->tableName == null) {
            throw new Doggy_ActiveRecord_Exception('tableName is NULL!');
        }
        
        if(!is_null($data)){
            $this->setRawData($data);
        }
        
        if(is_null($this->sequenceName)){
            $this->sequenceName = $this->tableName;
        }
        
        if(is_null($this->_attributes)){
            $this->_initializeAttributes();
        }
        $this->_buildFindResult();
    }
    
    //~~~{{{ private method
    /**
     * 回滚创建的相关的model
     */
    private function _rollbackCreatedRelationModel(){
        foreach($this->_rollback_models as $key => $ids){
            if(empty($ids))continue;
            $relation = $this->RelationMap[$key];
            $class = $relation['class'];
            $model = new $class();
            try{
                $model->destroy($ids);
                unset($model);
            }catch(Doggy_ActiveRecord_Exception $e){
                Doggy_Log_Helper::error("rollback model failed,[key:$key class:$class ids:".@implode($ids),__METHOD__);
            }
        }
    }
    
    /**
     * 检查指定存在指定key的关系,如果存在则返回relation定义数组，否则抛出Doggy_ActiveRecord_Exception异常
     * 
     * @param string $key
     * @return array
     * @throws Doggy_ActiveRecord_Exception
     */
    private function _checkRelationKey($key){
        
        if(!isset($this->RelationMap[$key])){
            Doggy_Log_Helper::error('invalid relation key:'.$key,__METHOD__);
            throw new Doggy_ActiveRecord_Exception('invalid relation key:'.$key);
        }
        $relation = $this->RelationMap[$key];
        
        $class = isset($relation['class'])?$relation['class']:null;
        if(empty($class) || !class_exists($class)){
            Doggy_Log_Helper::error("[key: $key]relation class is null or not found:$class",__METHOD__);
            throw new Doggy_ActiveRecord_Exception('relation class is null or not found:'.$class);
        }
        $type = isset($relation['type'])?$relation['type']:null;
        if(empty($type)){
            Doggy_Log_Helper::error("[key:$key] relation type is null!",__METHOD__);
            throw new Doggy_ActiveRecord_Exception('relation type is null!');
        }
        return $relation;
    }
    
    /**
     * 根据model的属性值在数据库表中添加相应的记录
     * 
     * @access  private
     */
    private function _createRecord(){
        Doggy_Log_Helper::debug("Create model record.. ",__METHOD__);
        $sql = "INSERT INTO ".$this->tablelize();
        foreach ($this->_attributes as $k => $v) {
            if(!isset($this->_data[$k]))continue;
            $columns[] = $k;
            $values[] = $this->_data[$k];
            $holders[] = '?';
        }
        $sql.= ' ('.implode(', ',$columns).') VALUES ('.implode(', ',$holders).') ';
        try{
            self::getDba()->execute($sql,$values);
            $this->_new=false;
            Doggy_Log_Helper::debug("Create model record OK. ",__METHOD__);
        }catch(Doggy_Dba_Exception $e){
            Doggy_Log_Helper::error("Create Record failed,DBA Error:".$e->getMessage(),__METHOD__);
            throw new Doggy_ActiveRecord_Exception('Create record error:'.$e->getMessage());
        }
     }
     
    /**
     * 根据model的属性值更新数据库表中相应的记录
     * @return bool
     * @access private
     */
    private function _updateRecord(){
        $sql = "UPDATE ".$this->tablelize();
        foreach ($this->_attributes as $k => $v) {
            if(!isset($this->_data[$k])) continue;
            $pairs[] = " $k = ?";
            $values[] = $this->_data[$k];
        }
        $sql .= ' SET '.implode(', ',$pairs).' ';
        $sql .= " WHERE $this->primaryKey=? ";
        $values[] = $this->getId();
        try{
            self::getDba()->execute($sql,$values);
        }catch(Doggy_Dba_Exception $e){
            Doggy_Log_Helper::error("Update record failed,DBA error:".$e->getMessage(),__METHOD__);
            throw new Doggy_ActiveRecord_Exception("Update record failed,DBA error:".$e->getMessage());
        }
    }
     
    /**
     * 初始化ActiveRecord对应的Attributes信息
     *
     * 读取ActiveRecord对应的表的字段并缓存
     *
     * @internal
     * @return boolean
     */
    private function _initializeAttributes(){
        $table = $this->tablelize();
        try{
            $fields = self::getDba()->getFieldMetaList($table);
        }catch(Doggy_Dba_Exception $e){
            Doggy_Log_Helper::error("Error while fetch table $table fields:".$e->getMessage(), __METHOD__);
            return false;
        }
        $this->_attributes = $fields;
        
        return true;
    }
    
    /**
     * Resutrn ? holders
     * @param int $size
     * @return string
     */
    protected function _createBindSqlHolders($size){
        $holds= array_pad(array(),$size,'?');
        return implode(',',$holds);
    }
    
    //~~~{{{ public/protected method
    /**
     * Return internal cache enabled or not
     *
     * @return bool
     */
    static public function internalCacheEnabled() {
        return self::$_enable_internal_cache;
    }
    
    /**
     * Enable/Disable internal cache.
     *
     * @return void
     */
    static public function enableInternalCache() {
       self::$_enable_internal_cache = true;
    }
    
    /**
     * Enable/Disable internal cache.
     *
     * @return void
     */
    static public function disableInternalCache() {
        self::$_enable_internal_cache = false;
    }
    
        
	/**
     * 查找记录，返回全部符合匹配条件的记录
     *
     * 本方法是支持各种find的核心操作。
     * <p>
     * 可以传递一个关联数组$options，用来说明查询的条件，options支持的选项key有:
     * 
     * condition: string,SQL查询的Where条件语句(不包括WHERE关键字)
     * order: string,SQL的order语句，如 created_time DESC,name ASC,age ASC
     * size: 一个整数，表示要分页时每页的记录数,-1表示不分页，返回全部
     * page: 一个整数，表示返回页的索引号，如果设置了limit，则此参数默认为1
     * select: string,默认情况下,select * FROM table,如果你希望用具体的字段限定来替换*,那么可以指定字段列表,如 'name,age'
     * joins: string,SQL查询时需要附加的JOINS语句，比如"LEFT JOIN comments ON comments.post_id = id"
     * groupby:string SQL GROUPBY条件
     * vars: array,要传递的预编译参数数组，如果sql中使用了?这些占位符
     * 
     * @param array $options
     * @return Doggy_ActiveRecord_Base
     * 
     */
    public function find($options=array()){
        $sql = $this->_buildSqlByOptions($options);
        return $this->findBySql($sql,$options);
    }
    
    /**
     * 查找匹配指定ID的记录
     *
     * @param mixed $id  待查找的记录的id or id array 
     * @param array $options  find options array,see #find
     * @return Doggy_ActiveRecord_Base
     */
    public function findById($id=null,$options=array()){
        
        if(is_null($id))$id= $this->getId();
        
        if(is_array($id)){
            $options['condition'] = $this->primaryKey." in (".$this->_createBindSqlHolders(count($id)).") ";
            $options['vars'] = $id;
            return $this->find($options);
        }else{
            //cache id?
            if (self::$_enable_internal_cache) {
                $cached_data = Doggy_Cache_Manager::get($id,$this->className);
                if(!is_null($cached_data)){
                    $this->_result_data = $cached_data;
                    $this->_rowMode=true;
                    $this->_buildFindResult();
                    return $this;
                }
            }
            
            $options['condition']  = $this->primaryKey." = ? ";
            $options['vars'] = array($id);
            $this->findFirst($options);
            if(!empty($this->_result_data) && self::$_enable_internal_cache){
                Doggy_Cache_Manager::set($id,$this->_result_data,$this->className);
            }
            return $this;
        }
    }
    
    /**
     * 查找并返回匹配的第一条记录
     *
     * @param array $options condition or other options to find
     * @return Doggy_ActiveRecord_Base
     */
    public function findFirst($options=array()){
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
     * @param boolean $readonly 对象是否只读
     * @param int $limit 每页记录数量，-1表示全部
     * @param int $page 分页的页索引(1-based)
     * @param array $vars 要传递的预编译参数数组，如果sql中使用了?这些占位符
     * @param boolean $raw 是否直接返回表记录数据，如果true,则不为这些数据创建对象
     * 
     * @return Doggy_ActiveRecord_Base
     */
    public function findBySql($sql,$options=array()){
        $size=-1;
        $page=1;
        $vars=null;
        $readonly=false;
        $_first=false;
        extract($options,EXTR_IF_EXISTS);
        try{
            $result = self::getDba()->query($sql,$size,$page,$vars);
            //hacking for findFirst,is it right?
            if($_first){
                $result = empty($result)?array():$result[0];
                $this->_rowMode=true;
            }else{
                $this->_rowMode=false;
            }
        }catch(Doggy_Dba_Exception $e){
            Doggy_Log_Helper::error("Dba Error:".$e->getMessage(),__METHOD__);
            throw new Doggy_ActiveRecord_Exception("Find data failed:".$e->getMessage());
        }
        $this->_result_data = $result;
        $this->_buildFindResult();
        $this->afterFind($_first);
        return $this;
    }
    
    /**
     * 查找当前model的关联对象
     * 
     * 可以指定一个options数组，数组的格式和 Doggy_ActiveRecord_Base::RelationMap
     * 中的options数组一样，从而覆盖RelationMap中的预定义的参数
     * 
     * $foreign_key_value 是用于查询这个关联所需的外键的字段值，默认使用get*方法从model的当前属性里获取，通常是当前的id值,
     * BELONGS_TO关系则是通过调用get(foreign_key)来获得,
     * 因此你也可以直接提供需要查询外键字段的值。
     * 
     * @param string $key 在RelationMap中定义的关联的key
     * @param array $options 可选的关联选项
     * @param mixed $foreign_key_value 关系外键的值，可选，默认使用get*方法从model的当前属性里获取，通常是当前的id值
     * @throws Doggy_ActiveRecord_Exception
     * @return Doggy_ActiveRecord_Base
     */
    public function findRelationModel($key,$options=array(),$foreign_key_value=null){
        
        $relation = $this->_checkRelationKey($key);
        $type = $relation['type'];
        
        $options = isset($relation['options'])?array_merge($relation['options'],$options):$options;
        
        $class = $relation['class'];
        $model = new $class();
        $id = $this->getId();
        $other_table = $model->getTableName();
        $other_foreign_key = $other_table.'_id';
        $this_foreign_key  = $this->tableName.'_id';
        Doggy_Log_Helper::debug("find relation model:$key type:$type class:$class",__METHOD__);
        
        $foreign_key = isset($relation['foreign_key'])?$relation['foreign_key']:$this_foreign_key;
        $vars = isset($options['vars'])?$options['vars']:array();
        $condition = !empty($options['condition'])?$options['condition']." AND $foreign_key=?":"$foreign_key=?";
        
        switch($type) {
            
            case self::HAS_MANY:
                $vars[] = is_null($foreign_key_value)?$this->getId():$foreign_key_value;
                $options['vars']=$vars;
                $options['condition']=$condition;
                return $model->find($options);
                break;
                
            case self::HAS_ONE:
                $vars[] = is_null($foreign_key_value)?$this->getId():$foreign_key_value;
                $options['vars']=$vars;
                $options['condition']=$condition;
                return $model->findFirst($options);
                
            case self::BELONGS_TO:
                $other_id =$model->getPrimaryKey();
                $condition = !empty($options['condition'])?$options['condition']." AND $other_id=? ":" $other_id=? ";
                $foreign_key = isset($relation['foreign_key'])?$relation['foreign_key']:$other_foreign_key;
                $vars[] = is_null($foreign_key_value)?$this->get($foreign_key):$foreign_key_value;
                $options['vars']=$vars;
                $options['condition']=$condition;
                return $model->findFirst($options);
                
            case self::HAS_AND_BELONGS_TO_MANY:
                
                $join_table = $this->tablelize(isset($relation['join_table'])?$relation['join_table']:$this->getJoinTableName($this->tableName, $other_table));
                $this_foreign_key = isset($relation['this_foreign_key'])?$relation['this_foreign_key']:$this_foreign_key;
                $other_foreign_key = isset($relation['other_foreign_key'])?$relation['other_foreign_key']:$other_foreign_key;
                
                $joins = ' LEFT JOIN '. $this->tablelize($join_table).' ON '.$model->tablelize().'.'.$model->getPrimaryKey()." = $join_table.$other_foreign_key ";
                if (!empty($options['joins']))
                        $joins .= " " . $options['joins'];
        
                $options['joins'] = $joins;
                               
                $condition = " $join_table.$this_foreign_key = ? ";
                $vars[] = is_null($foreign_key_value)?$this->getId():$foreign_key_value;
                
                if(!empty($options['condition'])){
                    $condition = ' ( '.$options['condition']." ) AND ( $condition ) ";
                }
                $options['condition']=$condition;
                $options['vars']=$vars;
                
                return $model->find($options);
            default:
                Doggy_Log_Helper::error("[key:$key]Unknow relation type:$type",__METHOD__);
                throw new Doggy_ActiveRecord_Exception('Unknow relation type:'.$type);
        } 
    }
    /**
     * 创建一个空白的记录集
     *
     */
    public function insert(){
        $this->_data=array();
        $this->_relationModels=array();
        $this->_rollback_models=array();
        $this->_validateErrors=array();
        $this->setIsNew(true);
        $this->_buildFindResult();
    }
    
    
    /**
     * 保存当前model
     * 
     * 保存包括插入和更新，根据$this->getIsNew()来确定是插入还是更新。
     * 
     * 保存的过程如下:
     * - beforeValidation
     * - validate
     * - afterValidation
     * - beforeSave
     * 如果是新记录，则
     * 	- beforeCreate
     *  	- 保存BelongsTo类型的relation model
     * 		- 新建记录到数据库
     * 	－ afterCreate
     * 否则：
     * 	- beforeUpdate
     * 	- 更新记录到数据库
     *  - afterUpdate
     * - afterSave
     * - 保存关联model的数据，如果有
     *
     * @param array $data optional
     * @param bool $validate optional whether validate
     * @return  Doggy_ActiveRecord_Base
     * 
     * @throws Doggy_ActiveRecord_ValidateException
     * @throws Doggy_ActiveRecord_Exception
     */
    final public function save($data=null,$validate = true){
        
        Doggy_Log_Helper::debug("Begin save model..",__METHOD__);
        
        if($data){
            $this->_apply($data);
        }
        
        //如果是新建记录则生成主键的sequence值
        if($this->_new && is_null( $this->getId() ) ){
            $this->setId($this->genId());
        } 
        
        $this->beforeValidation();
        
        if($validate){
            if(!$this->validate()){
                throw new Doggy_ActiveRecord_ValidateException($this->popValidateError());
            }
        }
        $this->afterValidation();
        
        //保存belongsTo类型的关系数据
        if(in_array(self::BELONGS_TO,$this->AutoSaveRelationType)){
            Doggy_Log_Helper::debug("Save belgonsTo relation models...",__METHOD__);
            $this->saveBelongstoRelationModels();
        }
        
        $this->beforeSave();
        
        if($this->_new){
            $this->beforeCreate();
            try{
                $this->_createRecord();
            }catch(Doggy_ActiveRecord_Exception $e){
                $this->_rollbackCreatedRelationModel();
                $this->onCreateError($e);
                $this->onSaveError($e);
                throw $e;
            }
            $this->afterCreate();
        }else{
            $this->beforeUpdate();
            try{
                $this->_updateRecord();
            }catch(Doggy_ActiveRecord_Exception $e){
                $this->_rollbackCreatedRelationModel();
                $this->onUpdateError($e);
                $this->onSaveError($e);
                throw $e;
            }
            $this->afterUpdate();
        }
        $this->afterSave();
        $this->saveRelationModels();
        
        $this->_rollback_models=array();
        $this->_relationModels=array();
        
        //clear cached data
        $id = $this->getId();
        if($id){
            if (self::$_enable_internal_cache) {
				Doggy_Log_Helper::debug("clear cache [id $id]...",__METHOD__);
                Doggy_Cache_Manager::remove($id,$this->className);
            }
        }
        return $this;
    }

    
    /**
     * 保存HAS_ONE,HAS_MANY,HAS_AND_BELONGS_TO_MANY类型的关联model的数据
     * 
     * 注意:
     * BELONGS_TO类型的关系使用 saveBelongstoRelationModels 来保存
     * 
     * @param array $relationType 要保存的Relation的类型,省略则保存self::$AutoSaveRelationType中定义的全部类型
     * @return Doggy_ActiveRecord_Base
     * @see saveBelongstoRelationModels
     */
    protected function saveRelationModels($relationType=null){
        if(empty($this->_relationModels)){
            return $this;
        }
        if(is_null($relationType)){
            $relationType = $this->AutoSaveRelationType;
        }
        if(empty($relationType)) {
            return $this;
        }
        
        foreach ($this->_relationModels as $key=>$dataRows){
            $relation = $this->RelationMap[$key];
            $type = $relation['type'];
            $class = $relation['class'];
            $options = isset($relation['options'])?$relation['options']:array();
            
            if($type==self::BELONGS_TO || !in_array($type,$relationType)){
                continue;
            }
            $model = new $class();
            
            $id = $this->getId();
            $other_table = $model->getTableName();
            $other_foreign_key = $other_table.'_id';
            $this_foreign_key  = $this->tableName.'_id';
            Doggy_Log_Helper::debug("save relation:$key type:$type class:$class",__METHOD__);
            
            //多对多关系表的数据可以一次性全部处理
            if($type == self::HAS_AND_BELONGS_TO_MANY){
                
                $this_foreign_key = empty($relation['this_foreign_key'])?$this_foreign_key:$relation['this_foreign_key'];
                $other_foreign_key = empty($relation['other_foreign_key'])?$other_foreign_key:$relation['other_foreign_key'];
                $join_table = empty($relation['join_table'])?$this->getJoinTableName($this->tableName,$other_table):$relation['join_table'];
                $habm_other_fields = empty($relation['habm_other_fields'])?array():$relation['habm_other_fields'];
                
                $condition = empty($options['condition'])?null:$options['condition'];
                $vars      = empty($options['vars'])?array():$options['vars'];
                
                $this->deleteHABMTableData($join_table,$this_foreign_key,$id,$condition,$vars);
                $this->saveHABMTableData($this_foreign_key,$other_foreign_key,$join_table,$dataRows,$habm_other_fields);
                
                Doggy_Log_Helper::debug("SAVE relation model[$key => type:$type class:$class] OK!",__METHOD__);
                
                unset($model);
                continue;
            }
            //其他类型的关系数据需要逐个处理
            
            foreach($dataRows as $row){
                $model->insert();
                $model->setRawData($row);
                if(!is_null($model->getId())){
                    $model->setIsNew(false);
                }
                if($model->isNew()){
                    $log_rollback=true;
                }else{
                    $log_rollback=false;
                }
                switch ($type) {
                    case self::HAS_ONE:
                    case self::HAS_MANY:
                        $foreign_key= empty($relation['foreign_key'])?$this_foreign_key:$relation['foreign_key'];
                        $model->set($foreign_key,$id);
                        $model->save();
                        if($log_rollback){
                            $this->_rollback_models[$key][] = $model->getId();
                            Doggy_Log_Helper::debug("CREATE A NEW model[$key => type:$type class:$class]!",__METHOD__);
                        }
                        Doggy_Log_Helper::debug("SAVE relation model[$key => type:$type class:$class] OK!",__METHOD__);
                	    break;
                    default:
                        continue;
                }
                
            }
            unset($model);
        }
        return $this;
    }
    /**
     * 保存从属BELONGS_TO关系类型的models
     * 注意:
     * 其他类型的关系使用saveRelationModels来保存
     * 
     * @return 
     * @see saveRelationModels
     */
    protected function saveBelongstoRelationModels(){
        if(empty($this->_relationModels)){
            return $this;
        }
        foreach ($this->_relationModels as $key=>$dataRows){
            $relation = $this->RelationMap[$key];
            $type = $relation['type'];
            $class = $relation['class'];
            
            if($type != self::BELONGS_TO){
                continue;
            }
            $model = new $class();
            $id = $this->getId();
            $other_table = $model->getTableName();
            $other_key = $other_table.'_id';
            
            $foreign_key= empty($relation['foreign_key'])?$other_key:$relation['foreign_key'];
            
            Doggy_Log_Helper::debug("save belongsto relation:$key type:$type class:$class",__METHOD__);
            
            foreach($dataRows as $row){
                $model->insert();
                $model->setRawData($row);
                if(!is_null($model->getId())){
                    $model->setIsNew(false);
                }
                if($model->isNew()){
                    $log_rollback=true;
                }else{
                    $log_rollback=false;
                }
                $model->save();
                if($log_rollback){
                    $this->_rollback_models[$key][] = $model->getId();
                }
                $this->set($foreign_key,$model->getId());
            }
            unset($model);
        }
        return $this;
    }
    
  

    /**
     * 更新多对多关联表记录
     * 
     * 本方法删除当前model在关联表中的所有记录，然后再插入新的关联记录。
     * 
     */
    protected function saveHABMTableData($this_foreign_key,$other_foreign_key,$table,$rows,$habm_other_fields=array()){ 
        foreach($rows as $row){
            $vars = array();
            $fields = array();
            
            $vars[] = $this->getId();
            $fields[] = $this_foreign_key;
            
            $vars[] = $row[$other_foreign_key];
            $fields[] = $other_foreign_key;
            
            if(!empty($habm_other_fields)){
                foreach($habm_other_fields as $f){
                    if(isset($row[$f])){
                        $vars[]=$row[$f];
                        $fields[]=$f;
                    }
                }
            }
            $sql = "INSERT INTO $table (";
            $sql .= implode(',',$fields).')';
            $sql .= 'VALUES('.$this->_createBindSqlHolders(count($fields)).')';
            try{
                self::getDba()->execute($sql,$vars);
            }catch(Doggy_Dba_Exception $e){
                Doggy_Log_Helper::error('Save habm data failed,dba error:'.$e->getMessage(),__METHOD__);
                throw new Doggy_ActiveRecord_Exception('Save habm data failed,dba error:'.$e->getMessage());
            }
        }
        
    }
    
    /**
     * 添加需要自动保存的关联model，当前model保存后将会自动保存这些关联的model的数据
     * 
     * @param string $key
     * @param Doggy_ActiveRecord_Base $model
     * @throws Doggy_ActiveRecord_Exception
     * @return Doggy_ActiveRecord_Base
     */
    public function addRelationModel($key,$model){
        $relation = $this->_checkRelationKey($key);
        $class = $relation['class'];
        if(!$model instanceof $class){
            Doggy_Log_Helper::error("model[class:".get_class($model)."] is not a instance of given relation [key:$key class:$class]",__METHOD__);
            throw new Doggy_ActiveRecord_Exception("model[class:".get_class($model)."] is not a instance of given relation [key:$key class:$class]");
        }
        $relation_model_array = isset($this->_relationModels[$key])?$this->_relationModels[$key]:array();
        
        $relation_model_array[] = $model->getRawData();
        $this->_relationModels[$key] = $relation_model_array;
        return $this;
    }
    
    /**
     * 添加一条需要自动保存的关联model的数据，这些数据在当前model保存后将会自动保存相应的关联model中去
     * 
     * @param string $key
     * @param array $data 关联model的一条记录的数据
     * @return Doggy_ActiveRecord_Base
     */
    public function addRelationModelData($key,array $data){
        $this->_checkRelationKey($key);
        $relation_model_array = isset($this->_relationModels[$key])?$this->_relationModels[$key]:array();
        $relation_model_array[] = $data;
        $this->_relationModels[$key] = $relation_model_array;
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
            return $this->destroyAll($this->primaryKey. ' in ('.implode(',',$id).')');
        }
        
        $_id = $this->getId();
        
        if(!is_null($id)){
            $this->setId($id);
        }else{
            $id= $this->getId();
        }
        if(is_null($this->getId())){
            $this->setId($_id);
            throw new Doggy_ActiveRecord_Exception('id is NULL');
        }
        
        $_result = $this->_result;
        $_data = $this->_data;
        
        //load model data
        $this->findById($id);
        $this->_apply($this->_result);
        
        //here, actually destroy work
        
        try{
            $this->beforeDestroy();
            $this->delete($id);
            $this->destoryDependedModel();
            $this->afterDestroy();
        }catch(Doggy_ActiveRecord_Exception $e){
            //restore context
            $this->setId($_id);
            $this->_apply($_data);
            $this->_result = $_result;
            throw $e;
        }
        
        //restore context
        $this->setId($_id);
        $this->_apply($_data);
        $this->_result = $_result;
        //clear internal cache
        Doggy_Log_Helper::debug("clear cache [id $id]...",__METHOD__);
		if (self::$_enable_internal_cache) {
			Doggy_Cache_Manager::remove($id,$this->className);
		}
        return $this;
    }
    /**
     * spell error alias
     */ 
    public function destory($id=null){
        return $this->destroy($id);
    }
    /**
     * 查找出符合条件的对象,并调用其remove方法删除(同时触发callback)
     * @param string $condition
     * @param array $vars bind array
     * @return Doggy_ActiveRecord_Base
     */
    public function destroyAll($condition,$vars=null){
        $this->find(array('condition'=>$condition,'vars'=>$vars));
        $success =0;
        if($this->count()>0){
            for($i=0;$i<$this->count();$i++){
                $model = self::getModel($this[$i],$this->className);
                if($model->destroy())$success++;
            }
            unset($model);
        }
        Doggy_Log_Helper::debug("destroy $success objects!",__METHOD__);
        return $this;
    }
    /**
     * spell error alias
     */    
    public function destoryAll($condition,$vars=null){
        return $this->destroyAll($condition,$vars);
    }
    /**
     * 立即从数据库中删除指定id的记录而不先创建对象(该对象的callback将不被触发)
     * 
     * @param mixed $id
     * @return Doggy_ActiveRecord_Base
     */
    public function delete($id){
        return $this->deleteAll($this->primaryKey." = '$id'");
    }
    
    /**
     * 立即从数据库中删除符合条件的记录而不先创建ActiveRecord对象(callback将不被触发)
     *
     * @param string $condition
     * @param array $vars
     * @return Doggy_ActiveRecord_Base
     */
    public function deleteAll($condition=null,$vars=array()){
        $sql = "DELETE  FROM ".self::tablelize();
        if(!empty($condition)){
            $sql .= " WHERE $condition";
        }
        self::getDba()->execute($sql,$vars);
        return $this;
    }
    
    /**
     * 删除依赖当前model的关联model。
     * 
     * 默认会删除HAS_MANY,HAS_ONE,HABTM表数据
     * 
     */
    protected function destoryDependedModel(){
        if(empty($this->RelationMap)){
            return $this;
        }
        
        $relationType = $this->AutoDeleteRelationType;
        
        foreach ($this->RelationMap as $key=>$relation){
            $type = $relation['type'];
            $class = $relation['class'];
            $options = isset($relation['options'])?$relation['options']:array();
            $depend = isset($relation['depend'])?$relation['depend']:true;
            
            if(!$depend || $type==self::BELONGS_TO || !in_array($type,$relationType)){
                continue;
            }
            
            $id = $this->getId();
            $model = new $class();
            $other_table = $model->getTableName();
            $this_foreign_key = $this->tableName.'_id';
            $other_foreign_key= $other_table.'_id';
            
            
            $other_key = $other_table.'_id';
            
            if($type == self::HAS_AND_BELONGS_TO_MANY){
                Doggy_Log_Helper::debug("REMOVE the link current model with other model:[$key => class:$class]...",__METHOD__);
                $this_foreign_key = empty($relation['this_foreign_key'])?$this_foreign_key:$relation['this_foreign_key'];
                $other_foreign_key = empty($relation['other_foreign_key'])?$other_foreign_key:$relation['other_foreign_key'];
                $join_table = empty($relation['join_table'])?$this->getJoinTableName($this->tableName,$other_table):$relation['join_table'];
                
                $condition = empty($options['condition'])?null:$options['condition'];
                $vars      = empty($options['vars'])?array():$options['vars'];
                try{
                    $this->deleteHABMTableData($join_table,$this_foreign_key,$id,$condition,$vars);
                    Doggy_Log_Helper::debug("DELETE HABTM table ok,unlink success",__METHOD__);
                }catch(Doggy_ActiveRecord_Exception $e){
                    Doggy_Log_Helper::error("DELETE HABTM table FAILED,unlink FAILED,error:".$e->getMessage(),__METHOD__);
                }
                
                continue;
            }
            
            
            
            $foreign_key= empty($relation['foreign_key'])?$this_foreign_key:$relation['foreign_key'];
            
            $condition = empty($options['condition'])?"$foreign_key=?":$options['condition']." AND $foreign_key=?";
            $vars = empty($options['vars'])?array():$options['vars'];
            $vars[] = $id;
            
            try{
                Doggy_Log_Helper::debug("DESTORY depended model [key:$key => class:$class ]... ",__METHOD__);
                $model->destroyAll($condition,$vars);
                Doggy_Log_Helper::debug("DESTORY depended model [key:$key => class:$class ] OK! ",__METHOD__);
            }catch(Doggy_ActiveRecord_Exception $e){
                Doggy_Log_Helper::error("DESTORY depended model [key:$key => class:$class ] FAILED,error:".$e->getMessage(),__METHOD__);
            }
            unset($model);
        }
        return $this;
    }

    /**
     * 删除多对多关联表中的匹配外键的记录
     * 
     * @param string $table
     * @param string $foreign_key
     * @param string $foreign_value
     * @param string $condition
     * @param string $vars
     */
    protected function deleteHABMTableData($table,$foreign_key,$foreign_value,$condition=null,$vars=array()){
        $condition = empty($condition)?"$foreign_key=?":$condition." AND $foreign_key=?";
        $sql = "DELETE FROM $table WHERE $condition";
        $vars[]= $foreign_value;
        try{
            Doggy_Log_Helper::debug('deleteAllHabtm:SQL:'.$sql,__METHOD__);
            self::getDba()->execute($sql,$vars);
        }catch(Doggy_Dba_Exception $e){
            Doggy_Log_Helper::error('deleteHABMTableData failed,dba error:'.$e->getMessage(),__METHOD__);
            throw new Doggy_ActiveRecord_Exception('deleteHABMTableData failed,dba error:'.$e->getMessage());
        }
    }

    
    
    /**
     * 检验所需字段是否已经赋值
     *
     * @param array $fields
     * @return  boolean
     */
    public function validateRequird($fields=array()){
        $ok=true;
        for($i=0;$i<count($fields);$i++){
            $f=$fields[$i];
            $s = $this->get($f);
            if(empty($s) && $s!==0) {
                $this->pushValidateError($f.' is NULL');
                $ok=false;
            }
        }
        return $ok;
    }
    
    /**
     * 压入一条校验错误信息
     * 
     * @param string $msg
     * @return Doggy_ActiveRecord_Base
     */
    protected function pushValidateError($msg){
        $this->_validateErrors[]=$msg;
        return $this;
    }
    /**
     * 弹出全部的校验错误信息
     * 
     * @return array
     */
    protected function popValidateError(){
        $data = $this->_validateErrors;
        $this->_validateErrors=array();
        return $data;
    }
    

     /**
      * 将attributes中的属性值应用到当前ActiveRecord对象中
      * 
      * @param array $attributes
      * @return Doggy_ActiveRecord_Base
      */
     protected function _apply($attributes){
         if(!empty($attributes)){
             foreach($attributes as $key => $value){
                 $this->set($key,$value);
             }
         }
         return $this;
     }
    
     /**
      * 构建model的实例
      * 
      * @param string $modelClass
      * @param array $data
      * @return Doggy_ActiveRecord_Base
      */
     public static function getModel($data=null,$modelClass=__CLASS__){
         if(empty($modelClass) || !class_exists($modelClass)){
             throw new Doggy_ActiveRecord_Exception('model class is null or not exists.');
         }
         $obj = new $modelClass($data);
         return $obj;
     }
     
     /**
      * 返回model在数据库中的实际表名(tableName＋前后缀)
      *
      * @param string $name
      * @return string
      */
     public function tablelize($name=NULL){
         if(empty($name))$name= $this->tableName;
         return $this->tableNamePrefix.$name.$this->tableNameSuffix;
     }


     //-------------------
     /**
      * 根据选项构建SQL串
      *
      * @param array $options
      * @return string
      * @access protected
      */
     protected function _buildSqlByOptions($options=array()){
        $condition=null;
        $order=null;
        $joins=null;
        $select=null;
        $groupby=null;
        extract($options,EXTR_IF_EXISTS);
        $sql ='SELECT ';
        $sql.=  $select? "$select ":'* ';
        $sql.= "FROM ".$this->tablelize();
        
        if($joins){
         $sql .= " $joins ";
        }
        $sql.= !empty($condition)? " WHERE $condition ":'';
        $sql.= $groupby?" GROUP BY $groupby":'';
        $sql.= $order? " ORDER BY $order ":'';
        return trim($sql);
     }
    
    /**
     * 设置ActiveRecord使用的DBA实例
     *
     * @param  Doggy_Dba_Adapter $value
     * 
     * @return Doggy_Active_Record_Base
     */
    final public static function setDba($value){
        self::$_dba = $value;
    }
    /**
     * 返回ActiveRecord使用的DBA实例
     *
     * @return Doggy_Dba_Adapter
     */
    final public static function getDba(){
        if(is_null(self::$_dba)){
            self::$_dba = Doggy_Dba_Manager::get_model_dba();
        }
        return self::$_dba;
    }
    
    public function __sleep(){
        return array('_result_data','_data','_rowMode');
    }
    /*
    public function __wakeup(){
        self::$_dba = Doggy_Dba_Manager::getDefaultConnection();
    }
    */
    
    /**
     * 返回当前model的类名
     * 
     * @return string
     */
    public function getClassName(){
        return $this->className;
    }
    
    /**
      * Set model's sequence name
      *
      * @param  string $value
      * @return Doggy_ActiveRecord_Base
      */
    protected function setSequenceName($value){
    	$this->sequenceName = $value;
    	return $this;
    }
    /**
      * Returns  model's sequence name
      *
      * @return string
      */
    public function getSequenceName(){
    	return $this->sequenceName;
    }
    /**
     * 生成用于新Record的ID号
     *
     * 默认情况下，使用RecordActive的类名作为SequenceName，从DBA中
     * 返回一个Seq的当前值。
     * 你可以重写此方法来实现自己的ID生成策略
     *
     * @return int
     */
    public function genId(){
        return self::getDba()->genSeq($this->sequenceName);
    }
    
    /**
     * 返回model对应表的表名(不包括表前后缀)
     */
    public function getTableName(){
        return $this->tableName;
    }
   
    /**
     * 设置Model对应表的主键
     *
     * @param  string $key
     * @return Doggy_ActiveRecord_Base
     */
    public function setTableNamePrefix($key){
        $this->tableNamePrefix = $key;
        return $this;
    }
    
    /**
     * 返回model对应表的表名(不包括表前后缀)
     */
    public function getTableNamePrefix(){
        return $this->tableNamePrefix;
    }
    
    /**
      * Set model's table suffix
      *
      * @param  string $value
      * @return Doggy_ActiveRecord_Base
      */
    public function setTableNameSuffix($value){
    	$this->tableNameSuffix = $value;
    	return $this;
    }
    /**
     * Returns model's table suffix
     * 
     * @return string
     */
    public function getTableNameSuffix(){
        return $this->tableNameSuffix;
    }
    
   
    /**
     * 设置Model对应表的主键
     *
     * @param  string $key
     * @return Doggy_ActiveRecord_Base
     */
    public function setPrimaryKey($key){
        $this->primaryKey = $key;
        return $this;
    }

    /**
     * 返回Model表的主键名
     * 
     * @return string
     */
    public function getPrimaryKey(){
        return $this->primaryKey;
    }
    
    /**
     *  Returns a the name of the join table that would be used for the two
     *  tables.  The join table name is decided from the alphabetical order
     *  of the two tables.  e.g. "genres_movies" because "g" comes before "m"
     *
     * @param string $first
     * @param string $second
     * @return string
     */
    public function getJoinTableName($first, $second) {
        $tables = array();
        $tables["one"] = $first;
        $tables["many"] = $second;
        @asort($tables);
        return $this->tablelize(@implode("_", $tables));
    }
    
  
    
    /**
     * Build internal data result ArrayObject
     */
    protected function _buildFindResult(){
        if($this->_rowMode){
            $this->_result = new Doggy_ActiveRecord_Base_ResultRow($this->_result_data,$this,$this->RelationMap);
        }else{
            $this->_result = new Doggy_ActiveRecord_Base_ResultSet($this->_result_data,$this,$this->RelationMap);
        }
    }
    
    /**
     * 查找匹配指定条件的记录的数量，如果没有匹配则返回0
     * @param string $condition
     * @param array $vars
     * @param string $table
     * @return int
     */
    public function countIf($condition=null,$vars=null,$table=null){
        if(is_null($table)){
            $table = $this->tablelize();
        }else{
            $table = $this->tablelize($table);
        }
        $sql = 'SELECT COUNT(*) AS cnt FROM '.$table;
        if(!empty($condition)){
            $sql.=" WHERE $condition ";
        }
        $row = self::getDba()->query($sql,1,1,$vars);
        return $row[0]['cnt'];
    }
    
    /**
     * 数据库中是否存在指定id的model对象
     *
     * @param mixed $id array of int or int
     * @return boolean
     */
    public function has($id){
        if(is_array($id)){
            $condition = $this->primaryKey.' IN ('.$this->_createBindSqlHolders(count($id)).')';
            $vars = $id;
        }else{
            $condition = $this->primaryKey."= ?";
            $vars=array($id);
        }
        $count = $this->countIf($condition,$vars);
        return ($count>0);
    }

    /**
     * 数据库中是否存在符合条件的记录
     *
     * @param string $condition where clause
     * @param array $vars bind array
     * @return bool
     */
    public function ifHas($condition,$vars=null){
        $count = $this->countIf($condition,$vars);
        return ($count>0);
    }
    /**
     * ifHas的别名
     * 
     * @param string $condition
     * @param array $vars
     * @return boolean
     */
    public function hasIf($condition,$vars=null){
        return $this->ifHas($condition,$vars);
    }
    

    /**
     * 对result结果集的某些字段反序列化
     * 
     * 
     * @param string $fieldName
     * @param boolean $singleMode 单记录还是多记录
     */
    protected function unserializeResultData($fieldName,$singleMode){
        if(!count($this->_result)) return;
        if($singleMode){
            if(!isset($this->_result[$fieldName]))return;
            $this->_result[$fieldName]= empty($this->_result[$fieldName])?null:@unserialize($this->_result[$fieldName]);
        }else{
            for($i=0,$c=count($this->_result);$i<$c;$i++){
                if(!isset($this->_result[$i][$fieldName])) continue;
                $this->_result[$i][$fieldName] = empty($this->_result[$i][$fieldName])?null:@unserialize($this->_result[$i][$fieldName]);
            }
        }        
    }
    
    //*************************************************************
    //
    //----------------各类Accessor 方法-----------
    //
    /**
     * 返回主键ID值
     *
     * @return mixed
     */
    public function getId(){
        return $this->get($this->primaryKey);
    }
    
    /**
     * 设置ID值
     *
     * @param mixed $value
     * @return Doggy_ActiveRecord_Base
     */
    public function setId($value){
        $this->set($this->primaryKey,$value);
        return $this;
    }
    /**
     * 标识model当前是新记录还是旧记录
     *
     * @param bool $new
     * @return Doggy_ActiveRecord_Base
     */
    public function setIsNew($new){
        $this->_new = $new;
        return $this;
    }
    /**
     * 当前model是否为新记录
     * 
     * @return boolean
     * 
     */
    public function isNew(){
        return $this->_new;
    }
    
    /**
     * 将结果集以数组的形式返回
     * 
     * @return array
     */
    public function getResultArray(){
        return $this->_result->toArray();
    }
    
    /**
     * 将结果集及对应的特殊的字段(关联字段,magic field)映射为纯数组形式
     *
     * extend_fields => array(
     *       "m1"=>array(),
     *       "m2"=>array(),
     *       "wife"=>array("children"=>array("father"=>array("wife"=>array())))
     *   )
     * 
     * Example:
     * 
     * $model->getExtendResultArray(array(
     *       "m1"=>array(),
     *       "m2"=>array(),
     *       "wife"=>array("children"=>array("father"=>array("wife"=>array())))
     *   ));
     * 
     * 本方法用于将model对象及其关联对象和magicfield属性等转换为纯数组数据的形式.
     * 当前算法使用递归实现.
     * 
     * Note: extend_fields若空则和getResultArray效果相同
     * 
     * 
     * @param array $extend_fields  expanded fields rules
     * @return array
     */
    public function getExtendResultArray(array $extend_fields){
        
        $result = array();
        if(empty($this)) return $result;
        
        if(empty($extend_fields)) return $this->getResultArray();
        
        //single record, row mode
        if($this->_rowMode){
            $result = $this->getResultArray();
            return $result + $this->_mapRowField($this->_result,$extend_fields);
        }
        //multi records
        for ($i=0; $i < count($this->_result); $i++) { 
            $row = $this->_result[$i];
            $result[] = $row->toArray() + $this->_mapRowField($row,$extend_fields);
        }
        return $result;
        
    }
    
    private function _mapRowField($row,$extend_fields){
        $result = array();
        
        foreach ($extend_fields as $field => $child_fields) {
            $v = !is_null($row[$field])?$row[$field]:null;
            if(is_object($v)){
                if( $v instanceof Doggy_ActiveRecord_Base){
                    //child fields defined
                    if(!empty($child_fields)){
                        $result[$field] = $v->getExtendResultArray($child_fields);
                    }
                    //if no child filed map, just fetch result array
                    else{
                        $result[$field] = $v->getResultArray();
                    }
                }
                else{
                    Doggy_Log_Helper::warn("$field map to a unknown type object,mashall failed!",__METHOD__);
                    $result[$field] = serialize($v);
                }
            }
            else {
                $result[$field] = $v;
            }
            
        }
        return $result;
    }

    /**
     * 获得当前ActiveRecord指定属性值
     *
     * @param string $key
     * @return mixed
     */
    public function get($key){
        return isset($this->_data[$key])?$this->_data[$key]:null;
    }
    
    /**
     * 设置当前ActiveRecord的属性值
     * @param  string $key
     * @param  mixed $value
     * @return Doggy_ActiveRecord_Base
     */
    public function set($key,$value){
        //$this->_data[$key] = $this->$key = $value;
        $this->_data[$key] = $value;
        return $this;
    }
    /**
     * 设置Model的的属性数据集
     * @param array $data
     * @return Doggy_ActiveRecord_Base
     */
    public function setRawData($data){
        $this->_data = $data;
        $this->setIsNew($this->getId()== null);
        return $this;
    }
    
    /**
     * 返回Model绑定的Raw Data数组,其对应于对应数据库表中的一行记录
     * @deprecated 
     * @return array
     */
     public function getRawData(){
         return $this->_data;
     }
     /**
      * 获取model的虚拟字段的值
      * 
      * 这是默认的实现，在调用虚拟字段的方法前，将设置当前model
      * 的属性为rowData，调用后恢复原始值
      * 
      * 注意:子类可以重载此方法
      * 
      * @param string $name 要获取的字段名称
      * @param array $rowData 当前记录的裸数据
      * @return mixed
      */
     public function _magicField($name,$rowData){
         if(!isset($this->MagicField[$name])){
             return null;
         }
         $method = $this->MagicField[$name];
         $_data = $this->getRawData();
         $this->setRawData($rowData);
         
         if(is_array($method)){
             $result = call_user_func($method);
         }else{
             $result = $this->$method();
         }
         $this->setRawData($_data);
         return $result;
     }
    
     /**
      * 添加/修改magic field的实现
      * 
      * @param string $name
      * @param mixed $callback 回调函数,可以是string或者callback形式(类名/对象,方法名)
      * @return Doggy_ActiveRecord_Base
      */
     public function setMagicField($name,$callback){
         $this->MagicField[$name]=$callback;
         return $this;
     }
    //*************************************************************
    //
    //----------------SPL 接口实现-----------
    //
    /*----------------------------
     * ArrayAccess interface
     */

    /**
     * @return bool
     */
    public function offsetExists($offset){
        //note:
        //change since v1.2.2
        //follow for support model unserialized
        if(is_null($this->_result)){
            $this->_buildFindResult();
        }
        return $this->_result->offsetExists($offset);
    }
    /**
     * @return mixed
     */
    public function offsetGet($offset){
        if(is_null($this->_result)){
            $this->_buildFindResult();
        }
        return $this->_result->offsetGet($offset);
    }
    /**
     * @param int $offset
     * @param mixed $value
     */
    public function offsetSet($offset,$value){
        if(is_null($this->_result)){
            $this->_buildFindResult();
        }
        $this->_result->offsetSet($offset,$value);
    }
    /**
     * @param int $offset
     */
    public function offsetUnset($offset){
        if(is_null($this->_result)){
            $this->_buildFindResult();
        }
        $this->_result->offsetUnset($offset);
    }
    
    /*-----------------------------
     * Countable interface
     * 
     * @return int
     */
    public function count(){
        if(is_null($this->_result)){
            $this->_buildFindResult();
        }
        return $this->_result->count();
    }
    //Iterator interface
    public function current(){
        if(is_null($this->_result)){
            $this->_buildFindResult();
        }
        return $this->_result->current();
    }
    public function key(){
        if(is_null($this->_result)){
            $this->_buildFindResult();
        }
        $this->_result->key();
    }
    public function next(){
        if(is_null($this->_result)){
            $this->_buildFindResult();
        }
        $this->_result->next();
    }
    public function rewind(){
        if(is_null($this->_result)){
            $this->_buildFindResult();
        }
        $this->_result->rewind();
    }
    public function valid(){
        if(is_null($this->_result)){
            $this->_buildFindResult();
        }
        return $this->_result->valid();
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
     *     - beforeValidation
     *     - validate
     *     - afterValidataion
     *     - beforeSave
     *     - beforeCreate
     *     - afterCreate
     *     - afterSave
     *
     * update时:
     *     - beforeValidation
     *     - validate
     *     - afterValidation
     *     - beforeSave
     *     - afterUpdate
     *     - beforeUpdate
     *     - afterSave
     *
     * destroy:
     *     - beforeDestory
     *     - afterDestory
     *
     *
     *
     */

    /**
     * before_create callback
     * @abstract
     * @return bool
     */
    protected function beforeCreate(){return true;}
    /**
     * after_create callback
     * @abstract
     * @return bool
     */
    protected function afterCreate(){return true;}

    /**
     * before_save callback
     * @abstract
     * @return bool
     */
    protected function beforeSave(){return true;}
    /**
     * after_save callback
     * @abstract
     * @return bool
     */
    protected function afterSave(){return true;}
    /**
     * before_update callback
     * @abstract
     * @return bool
     */
    protected function beforeUpdate(){return true;}

    /**
     * after_update callback
     * @abstract
     * @return bool
     */
    protected function afterUpdate(){return true;}

    /**
     * before_validation callback
     * @abstract
     * @return bool
     */
    protected function beforeValidation(){return true;}
    /**
     * after_validation callback
     * @abstract
     * @return bool
     */
    protected function afterValidation(){return true;}

    /**
     * 校验数据有效性方法,用户应重载以便实现数据检查
     * @abstract
     * @return bool
     * @throw Doggy_ActiveRecord_ValidateException
     */
    protected function validate(){return true;}

    /**
     * before_destory callback
     * @return bool
     */
    protected function beforeDestroy(){}
    /**
     * after_destroy callback
     * @return bool
     */
    protected function afterDestroy(){}
    /**
     * callback after find got result but before return it
     * @param boolean $singelMode 指示当前是否为单记录模式
     */
    protected function afterFind($singelMode){}
    
    /**
     * 保存失败的时候回调
     * 
     * @param Doggy_ActiveRecord_Exception $e
     * @return boolean 是否要抑制异常,true则不向上抛出异常
     */
    protected function onSaveError($e){}
    /**
     * 创建失败的时候回调
     * 
     * @param Doggy_ActiveRecord_Exception $e
     */
    protected function onCreateError($e){}
    
	/**
	 * 更新失败的时候回调
	 * 
     * @param  Doggy_ActiveRecord_Exception $e
     */
    protected function onUpdateError($e){}
     
}

/**
 * 行记录,代表查找结果集的一条记录
 * 
 * 内部类,用于实现SPL接口和关联对象的LazyLoad
 */
class Doggy_ActiveRecord_Base_ResultRow extends Doggy_Object implements Countable, Iterator, ArrayAccess {
    protected $_data=array();
    /**
     * @var Doggy_ActiveRecord_Base
     */
    public $_model;
    protected $_relations;
    public function __construct(&$data,$model,$relations){
        $this->_data = &$data;
        $this->_model = $model;
        $this->_relations = $relations;
    }
    public function offsetExists($offset){
        return isset($this->_data[$offset]);
    }
    public function offsetGet($offset){
        if(!isset($this->_data[$offset])){
            if(isset($this->_relations[$offset])){
                $_bak = $this->_model->getRawData();
                $this->_model->setRawData($this->_data);
                $relation = $this->_model->findRelationModel($offset);
                $this->_model->setRawData($_bak);
                return $relation;
            }
            return $this->_model->_magicField($offset,$this->_data);
        }
        return $this->_data[$offset];
    }
    public function offsetSet($offset,$value){
        $this->_data[$offset] = $value;
    }
    public function offsetUnset($offset){
        unset($this->_data[$offset]);
    }
    //countable
    public function count(){
        return count($this->_data);
    }
    //iterator
    public function key(){
        return key( $this->_data);
    }
    public function rewind(){
        return reset( $this->_data );
    }
    public function valid(){
        return current( $this->_data ) !== false;
    }
    public function current(){
        return current($this->_data);
    }
    public function next(){
        return next( $this->_data);
    }
    public function toArray(){
        return $this->_data;
    }
    public function keys(){
        return array_keys($this->_data);
    }
}
/**
 * 查找结果集
 * 
 * 内部类,用于封装找到的所有行记录(Find类方法的结果)
 */
class Doggy_ActiveRecord_Base_ResultSet extends Doggy_ActiveRecord_Base_ResultRow {
    protected $_rows=array();
    protected $_pointer = 0;
    
    public function offsetGet($offset){
        
        if(isset($this->_data[$offset])){
            if(!isset($this->_rows[$offset])){
                $this->_rows[$offset]= new Doggy_ActiveRecord_Base_ResultRow($this->_data[$offset],$this->_model,$this->_relations);
            }
            return $this->_rows[$offset];
        }else{
            return null;
        }
    }
    
    public function offsetUnset($offset){
        unset($this->_rows[$offset]);
        return parent::offsetUnset($offset);
    }
    
    public function current(){
        if(!$this->valid()){
            return false;
        }
        return $this->offsetGet($this->_pointer);
    }
    public function rewind(){
        $this->_pointer=0;
    }
    public function next(){
        return ++$this->_pointer;
    }
    public function valid(){
        return $this->_pointer < $this->count();
    }
    public function key(){
        return $this->_pointer;
    }
   
}
/** vim:sw=4:expandtab:ts=4 **/
?>