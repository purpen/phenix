<?php
/**
 * Lite wrapper for mongo db driver
 *
 */
class DoggyX_Mongo_Db {

    protected $mongo;
    protected $db;
    protected $db_name = 'test';

    public function __construct($options=array()) {
        $host = isset($options['host'])?$options['host']:'mongodb://127.0.0.1';
        //unset($options['host']);
        
		$mos = array();
		// 集群
		if (isset($options['replicaSet'])){
			$mos['replicaSet'] = $options['replicaSet'];
		}
		// 读写分离
		if (isset($options['readPreference'])){
			$mos['readPreference'] = $options['readPreference'];
		}
		// 设置权限
		if (isset($options['username'])){
			$mos['username'] = $options['username'];
		}
		if (isset($options['password'])){
			$mos['password'] = $options['password'];
		}
        
        // 建立连接
        $mongo = new MongoClient($host, $mos);
        
        if (isset($options['slaveOk'])) {
            $mongo->setSlaveOkay($options['slaveOk']);
        }
        $this->db_name = isset($options['db'])?$options['db']:'test';
        $this->db = $mongo->selectDB($this->db_name);
        // WARN: follow two options only valid after v1.5.1+, but we don't care ;-).
        if (isset($options['w'])) {
            $this->db->w = $options['w'];
        }
        if (isset($options['wtimeout'])) {
            $this->db->wtimeout = $options['wtimeout'];
        }
        $this->mongo = $mongo;
    }
    
    /**
     * Returns a MongoId from a string, MongoId, array
     *
     * @param mixed $obj
     * @return MongoId
     **/
    public static function id($obj) {
        if (empty($obj)) {
            return null;
        }
        if ($obj instanceof MongoId) {
            return $obj;
        }
        if (is_string($obj)) {
            return new MongoId($obj);
        }
        if (is_object($obj)) {
            return new MongoId($obj->_id);
        }
        return $obj;
    }

    /**
     * Returns true if the value passed appears to be a Mongo database reference
     *
     * @param $value
     * @return bool
     * @internal param mixed $obj
     */
    public static function is_ref($value) {
        if (!is_array($value)) {
            return false;
        }
        return MongoDBRef::isRef($value);
    }

    /**
     * Returns a Mongo database reference created from a collection and an id
     *
     * Note: Don't use db ref if possbile.
     * 
     * @param string $collection
     * @param mixed $id
     * @deprecated
     * @return array
     **/
    public static function create_ref($collection, $id) {
        return array('$ref' => $collection, '$id' => self::id($id));
    }
    
    public function select_collection($collection_name) {
        return $this->db->selectCollection($collection_name);
    }

    /**
     * Returns the Mongo object array that a database reference points to
     *
     * @param array $dbref
     * @return array
     **/
    public function get_ref($dbref) {
        return $this->db->getDBRef($dbref);
    }

    /**
     * Recursively expands any database references found in an array of references,
     * and returns the expanded object.
     *
     * @param mixed $value
     * @return mixed
     **/
    public function expand_refs($value) {
        if (is_array($value)) {
            if (self::isRef($value)) {
                return $this->get_ref($value);
            } else {
                foreach ($value as $k => $v) {
                    $value[$k] = $this->expand_refs($v);
                }
            }
        }
        return $value;
    }

    /**
     * Find and return query result array.
     *
     * Pass the query and options as array objects (this is more convenient than the standard
     * Mongo API especially when caching)
     *
     * $options may contain:
     *   fields - the fields to retrieve
     *   sort - the criteria to sort by
     *   page - the start page
     *   size - the number of per page
     *   return_cursor - just return the result cursor.
     *
     * @param string $collection
     * @param array $query
     * @param array $options
     * @return mixed
     **/
    public function find($collection, array $query = array(), array $options = array()) {
        $fields = array();
        $sort = null;
        $page = null;
        $size = null;
        $return_cursor = false;
        $tailable = false;

        extract($options,EXTR_IF_EXISTS);
        
        if (!empty($page) && $size >0) {
            $limit = $size;
            $skip = max( ($page-1) * $size,0);
        }
        else {
            $limit = null;
            $skip = null;
        }
        $col = $this->db->selectCollection($collection);
        $result = $col->find($query,$fields);
        if ($sort) {
            $result->sort($sort);
        }
        if ($limit) {
            $result->limit($limit);
        }
        if ($skip) {
            $result->skip($skip);
        }
        if ($tailable) {
            $result->tailable($tailable);
        }
        if ($return_cursor) {
            return $result;
        }
        $array = array();
        foreach ($result as $val) {
            $array[] = $val;
        }
        return $array;
    }
    
    /**
     * Do a find() but return an array populated with one field value only
     *
     * @param string $collection
     * @param string $field
     * @param array $query
     * @param array $options
     * @return array
     **/
    public function find_field($collection, $field, array $query = array(), array $options = array()) {
        $options['fields'] = array($field => 1);
        $result = $this->find($collection, $query, $options);
        $array = array();
        foreach ($result as $val) {
            $array[] = $val[$field];
        }
        return $array;
    }

    
    /**
     * Find a single object -- like Mongo's findOne() but you can pass an id as a shortcut
     *
     * @param string $collection
     * @param mixed $id
     * @param array $fields
     * @return array
     **/
    public function first($collection, $id, array $fields = array()) {
        $col = $this->db->selectCollection($collection);
        if (!is_array($id)) {
            $id = array('_id' => self::id($id));
        }
        return $col->findOne($id, $fields);
    }

    /**
     * Count the number of objects matching a query in a collection (or all objects)
     *
     * @param string $collection
     * @param array $query
     * @return integer
     **/
    public function count($collection, array $query = array()) {
        $col = $this->db->selectCollection($collection);
        if ($query) {
            $res = $col->find($query);
            return $res->count();
        } else {
            return $col->count();
        }
    }

    /**
     * Save a Mongo object -- just a simple shortcut for MongoCollection's save()
     *
     * @param string $collection
     * @param array $data
     * @return boolean
     **/
    public function save($collection, $data) {
        $col = $this->db->selectCollection($collection);
        return $col->save($data);
    }
    
    public function insert($collection,$data) {
        $col = $this->db->selectCollection($collection);
        $ok = $col->insert($data);
        return $data;
    }

    /**
     * Shortcut for MongoCollection's update() method
     * 
     * Note about options:
     * $upsert: If no document matches $criteria, a new document will be created from $criteria and $newobj
     * $multiple: All documents matching $criteria will be updated.
     * $safe: Can be a boolean or integer, defaults to FALSE. If FALSE, the program continues executing without waiting for a database response.
     *  If TRUE, the program will wait for the database response and throw a MongoCursorException if the update did not succeed.
     *  If safe is an integer, will replicate the update to that many machines before returning success (or throw an exception if the replication
     *  times out, see wtimeout). This overrides the w variable set on the collection.
     * 
     * @param string $collection
     * @param array $criteria
     * @param array $newobj
     * @param boolean $upsert default is false
     * @param boolean $multiple default is false
     * @param mixed $safe default is false
     * @return void
     **/
    public function update($collection, $criteria, $newobj, $upsert = false, $multiple = false,$safe = true, $w=null) {
        $col = $this->db->selectCollection($collection);
        $options = array(
            'upsert' => $upsert,
            'multiple' => $multiple,
        );
        if ($safe) {
            $options['w'] = 1;
        }
        if (!is_null($w)) {
            $options['w'] = (int) $w;
        }
        return $col->update($criteria, $newobj, $options);
    }
    /**
     * Update all matched rows
     *
     * @param string $collection
     * @param string $criteria
     * @param string $newobj
     * @param string $upsert
     * @return void
     */
    public function update_all($collection,$criteria,$newobj,$upsert = false, $safe = true) {
        return $this->update($collection,$criteria,$newobj,$upsert,true,$safe);
    }

    /**
     * Shortcut for MongoCollection's update() method, performing an upsert
     *
     * @param string $collection
     * @param array $criteria
     * @param array $newobj
     * @param boolean $multiple
     * @param boolean $safe
     * @return boolean
     **/
    public function upsert($collection, $criteria, $newobj,$multiple=false,$safe=true) {
        return $this->update($collection, $criteria, $newobj, true,$multiple,$safe);
    }
    
    /**
     * Shortcut for MongoCollection's update() method, performing an set
     *
     * @param string $collection 
     * @param array $criteria 
     * @param array $newobj 
     * @param bool $upsert 
     * @param bool $multiple
     * @param bool $safe
     * @return bool
     */
    public function set($collection,$criteria,$newobj,$upsert = false,$multiple=false,$safe = false) {
        return $this->update($collection,$criteria,array('$set'=>$newobj),$upsert,$multiple,$safe);
    }

    /**
     * Increment a field
     *
     * @param string $collection
     * @param string $criteria
     * @param string $field
     * @param string $inc
     * @param bool $upsert
     * @param bool $multiple
     * @param bool $safe
     */
    public function inc($collection,$criteria,$field,$inc,$upsert=true,$multiple=false,$safe=false) {
        return $this->update($collection,$criteria,array('$inc'=>array($field=>$inc)),$upsert,$multiple,$safe);
    }
    
    public function push($collection,$criteria,$field,$value,$upsert=true,$multiple=false,$safe=false) {
        return $this->update($collection,$criteria,array('$push'=>array($field=>$value)),$upsert,$multiple,$safe);
    }
    
    public function push_all($collection,$criteria,$field,$values,$upsert=true,$multiple=false,$safe=false) {
        return $this->update($collection,$criteria,array('$pushAll'=>array($field=>$values)),$upsert,$multiple,$safe);
    }
    
    public function pull($collection,$criteria,$field,$value,$multiple=false,$safe = false) {
        return $this->update($collection,$criteria,array('$pull'=>array($field=>$value)),false,$multiple,$safe);
    }
    
    public function pull_all($collection,$criteria,$field,$values,$multiple=false,$safe = false) {
        return $this->update($collection,$criteria,array('$pullAll'=>array($field=>$values)),false,$multiple,$safe);
    }

    /**
     * Shortcut for MongoCollection's remove() method, with the option of passing an id string.
     * $safe option: can be boolean or interger
     *  If TRUE, the program will wait for the database response and throw a MongoCursorException if the update did not succeed.
     *  If safe is an integer, will replicate the update to that many machines before returning success (or throw an exception if the replication times out, see wtimeout). This overrides the w variable set on the collection.
     *
     * @param string $collection
     * @param array $criteria
     * @param boolean $just_one
     * @param mixed $safe Boolean or interger.
     * @return boolean
     **/
    public function remove($collection, $criteria, $just_one = false, $safe = true, $w=null) {
        $col = $this->db->selectCollection($collection);
        if (!is_array($criteria)) {
            $criteria = array('_id' => self::id($criteria));
        }
        $options = array('justOne' => $just_one);
        if ($safe) {
            $options['w'] = 1;
        }
        if (!is_null($w)) {
            $options['w'] = (int) $w;
        }
        return $col->remove($criteria, $options);
    }

    /**
     * Shortcut for MongoCollection's drop() method
     *
     * @param string $collection
     * @return boolean
     **/
    public function drop($collection) {
        $col = $this->db->selectCollection($collection);
        return $col->drop();
    }

    /**
     * Shortcut for MongoCollection's batchInsert() method
     *
     * @param string $collection
     * @param array $array
     * @param boolean $safe
     * @return boolean
     **/
    public function batch_insert($collection, $array,$safe = false) {
        $col = $this->db->selectCollection($collection);
        return $col->batchInsert($array, array('safe' => $safe));
    }

    /**
     * Shortcut for MongoCollection's ensureIndex() method
     *
     * @param string $collection
     * @param array $keys
     * @return boolean
     **/
    public function ensure_index($collection, $keys) {
        $col = $this->db->selectCollection($collection);
        return $col->ensureIndex($keys);
    }

    /**
     * Ensure a unique index (there is no direct way to do this in the MongoCollection API now)
     *
     * @param string $collection
     * @param array $keys
     * @return boolean
     **/
    public function ensure_unique_index($collection, $keys) {
        $name_parts = array();
        foreach ($keys as $k => $v) {
            $name_parts[] = $k;
            $name_parts[] = $v;
        }
        $name = implode('_', $name_parts);
        $col = $this->db>selectCollection('system.indexes');
        return $col->save(array('ns' => $this->db_name . ".$collection",
                         'key' => $keys,
                         'name' => $name,
                         'unique' => true));
    }

    /**
     * Shortcut for MongoCollection's getIndexInfo() method
     *
     * @param string $collection
     * @return array
     **/
    public function get_index_info($collection) {
        $col = $this->db->selectCollection($collection);
        return $col->getIndexInfo();
    }

    /**
     * Shortcut for MongoCollection's deleteIndexes() method
     *
     * @param string $collection
     * @return boolean
     **/
    public function delete_indexes($collection) {
        $col = $this->db->selectCollection($collection);
        return $col->deleteIndexes();
    }
    /**
     * Shortcut for MongoDb's getGridFS
     *
     * @return void
     */
    public function get_fs() {
        return $this->db->getGridFS();
    }

    /**
     * Like find, but only against MongoGridFs
     *
     * @param array $query
     * @param array $options
     * @return array
     * @internal param string $fields
     */
    public function fs_find(array $query=array(),array $options=array()) {
        $fields = array();
        $sort = null;
        $page = null;
        $size = null;
        
        extract($options,EXTR_IF_EXISTS);
        
        if (!empty($page) && $size >0) {
            $limit = $size;
            $skip = max( ($page-1) * $size,0);
        }
        else {
            $limit = null;
            $skip = null;
        }
        
        $result = $this->db->getGridFS()->find($query,$fields);
        if ($sort) {
            $result->sort($sort);
        }
        if ($limit) {
            $result->limit($limit);
        }
        if ($skip) {
            $result->skip($skip);
        }
        
        $array = array();
        foreach ($result as $val) {
            $array[] = $val;
        }
        return $array;
        
    }
    
    public function create_collection($collection_name, $capped= FALSE, $size= 0, $max= 0 ){
        return $this->db->createCollection($collection_name,$capped,$size,$max);
    }
    
    public function drop_collection($collection_name) {
        return $this->db->dropCollection($collection_name);
    }
    
    public function execute($code, array $args = array()) {
        $response = $this->db->execute($code,$args);
        return isset($response['retval'])?$response['retval']:$response;
    }
    /**
     * Call server stored js function
     *
     * @param string $fun_name 
     * @param array $named_args 
     * @return mixed
     */
    public function call_function($fun_name, array $named_args = array()) {
        $response = $this->db->execute(new MongoCode("$fun_name()",$named_args));
        return isset($response['retval'])?$response['retval']:$response;
        
    }
    /**
     * Save javascript as server side function.
     *
     * @param string $fun_name 
     * @param string $fun_body Raw javascript code string
     * @return void
     */
    public function store_server_function($fun_name,$fun_body) {
        $code = sprintf('
        var _fun = %s;
        db.system.js.save({_id:"%s", value: _fun });
        ',$fun_body,$fun_name);
        return $this->execute($code);
    }
    /**
     * Wrapper of findAndModfiy command:
     *
     * Options:
     * query	 a filter for the query,default is	{}
     * sort	     if multiple docs match, choose the first one in the specified sort order as the object to manipulate,default is {}
     * remove	 set to a true to remove the object before returning
     * update	 a modifier array
     * new	     set to true if you want to return the modified object rather than the original. Ignored for remove.
     * fields	 see Retrieving a Subset of Fields (1.5.0+)	 default is All fields.
     * upsert	 create object if it doesn't exist.
     *
     * @param string $collection
     * @param array $options
     * @return mixed
     */
    public function find_and_modify($collection,$options = array()) {
        $result = $this->db->command(array('findAndModify' => $collection) + $options);
        return $result['ok'] ? $result['value']: $result;
    }
    /**
     * Simple command wrapper
     *
     * @param array $command 
     * @return array
     */
    public function command($command) {
        return $this->db->command($command);
    }
}
