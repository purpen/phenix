<?php
/**
 * Mysql Database Driver
 * 
 * This driver use mysqli extension, it also support "MysqlND", the new PHP natvie mysql extension,
 * and,it will auto check and active the mysqlnd powered feature.
 *
 * 
 * @author night
 */
class Doggy_Db_Driver_Mysql extends Doggy_Db_Driver_Abstract {
    
    protected static  $_genIDSQL = 'UPDATE %s SET id=LAST_INSERT_ID(id+1)';
	protected static  $_genSeqSQL = 'CREATE TABLE %s (id INT NOT NULL)';
	protected static  $_genSeq2SQL = 'INSERT INTO %s VALUES (%s)';
	protected static  $_dropSeqSQL = 'DROP TABLE %s';
	protected static  $_getSeqSQL = 'SELECT LAST_INSERT_ID() AS id';
	protected static  $_metaTablesSQL ='SHOW TABLES';
	protected static  $_metaColumnSQL ='SHOW COLUMNS FROM `%s`';
	
    protected $mysqli;
    
    private $stmt_results=array();
    private $stmt_var_bound=false;
    
    private $enable_mysqlnd=false;
    /**
     * Auto check mysqlnd feature
     * 
     * @param string $dsn
     */
    public function __construct($dsn){
        parent::__construct($dsn);
        $this->enable_mysqlnd = function_exists('mysqli_fetch_all');
        if ($this->enable_mysqlnd) {
            // Doggy_Log_Helper::debug('***MysqlND is actived.***');
        }
    }
    
    public function enable_mysqlnd($v) {
        $this->enable_mysqlnd = $v;
    }
    /**
     * connect to mysql database
     *
     * @return void
     **/
    protected function do_connect() {
        
        mysqli_report(MYSQLI_REPORT_OFF);
        
        $host = $this->uri['host'];
        $port = isset($this->uri['port'])?$this->uri['port']:null;
        $user = isset($this->uri['user'])?$this->uri['user']:'root';
        $password = isset($this->uri['pass'])?$this->uri['pass']:'';
        $db = str_replace('/', '',$this->uri['path']) ;
        if(!empty($this->uri['query'])){
            parse_str($this->uri['query'],$args);
        }
        else {
            $args = array();
        }
        Doggy_Log_Helper::debug("Connect to mysql [host:$host port:$port user:$user passwd:$password ]");

        $mysqli =  new mysqli($host,$user,$password,$db,$port);
        
        if (mysqli_connect_errno()) {
            Doggy_Log_Helper::error("Cannot connect to database[host:$host port:$port user:$user passwd:$password ],Error:".mysqli_connect_error());
            throw new Doggy_Db_Exception("Cannot connect to database.");
        }
        
        if(isset($args['charset'])){
            $charset = $args['charset'];
            
            Doggy_Log_Helper::debug("Set charset :$charset");
            
            if (!$mysqli->set_charset($charset)) {
                Doggy_Log_Helper::error("Cannot set mysql charset:[$charset]");
                throw new Doggy_Db_Exception("Cannot set mysql charset[$charset]");
            }
        }
        $this->mysqli=$mysqli;
        return true;
    }
    
    protected function do_close(){
        if($this->mysqli){
            $this->mysqli->close();
        }
        unset($this->mysqli);
        $this->_connected = false;
    }

    public function execute($sql,$vars=array()){
        if(!$this->connect()){
            throw new Doggy_Db_Exception("Database connect failed");
        }
        $mysqli = $this->mysqli;
        if(strpos($sql,'?')!==false){
            $ok = $this->execute_statement($mysqli,$sql,$vars,false);
        }else{
            $ok=$mysqli->query($sql);
            if($mysqli->errno){
                Doggy_Log_Helper::error("SQL ERROR:".$mysqli->error.' SQL:'.$sql);
                throw new Doggy_Db_Exception('SQL ERROR:'.$mysqli->error);
            }
        }
        return $ok;
    }

    /**
     * Construct paged sql
     *
     * @param string $string
     * @param int $size
     * @param int $page
     * @return string
     **/
    protected function build_page_sql($sql,$size,$page){
        if($size>0){
            if($page>=1){
                $offset = ($page-1)*$size;
            }else{
                $offset =-1;
            }
        }else{
            $page=-1;
            $size=-1;
        }
        if($size>0){
            $sql .= ' LIMIT '.$size;
            if($offset>0){
                $sql.=' OFFSET '.$offset;
            }
        }
        return $sql;
    }
    
	/**
	 * 添加 by purpen
	 */
    public function makeValuesReferenced($arr){
	    $refs = array();
	    foreach($arr as $key => $value)
	        $refs[$key] = &$arr[$key];
	    return $refs;

	}
    
    /**
     * Prepare and execute the sql statement
     * 
     * if in result mode ( $resultMode is true)
     * then return mysqli_stmt object
     * otherwise will return execute result(true or false).
     *
     * @param mysqli $mysqli
     * @param string $string
     * @param array  $vars
     * @param boolean $resultMode
     * @return mixed
     **/
    protected function execute_statement($mysqli,$sql,$vars,$result_mode=true){
        $stmt = $mysqli->prepare($sql);
        if(!$stmt){
            throw new Doggy_Db_Exception('Prepare SQL failed:'.$mysqli->error);
        }
        if ($stmt->param_count != count($vars)) {
            throw new Doggy_Db_Exception('The parameter holder is not same as invoke parameter array.');
        }
        $a = '';
        foreach ($vars  as $k => $v) {
            if (is_string($v)) $a .= 's';
            else if (is_integer($v)) $a .= 'i'; 
            else $a .= 'd';
        }
        array_unshift($vars,$a);
        #call_user_func_array(array($stmt,'bind_param'),$vars);
		# 修改为php5.3.3 by purpen
		call_user_func_array(array($stmt, 'bind_param'), $this->makeValuesReferenced($vars));
        
        $ok = $stmt->execute();
        if($mysqli->errno){
            Doggy_Log_Helper::error('SQL ERROR:'.$mysqli->error." SQL:$sql");
            throw new Doggy_Db_Exception('SQL ERROR:'.$mysqli->error);
        }
        if($result_mode){
            return $stmt;
        }else{
            $stmt->close();
            return $ok;
        }
    }
    
    public function query($sql,$size=-1,$page=1,$vars=array(),$fetch_mode=MYSQLI_ASSOC){
        
        if(!$this->connect()){
            throw new Doggy_Db_Exception("Database connect failed.");
        }
        
        $sql = $this->build_page_sql($sql,$size,$page);
        
        $mysqli = $this->mysqli;
        //if has statement bind variable
        if ( strpos($sql,'?')!==false ) {
            $stmt = $this->execute_statement($mysqli,$sql,$vars);
            $rows = $this->_fetch_stmt_result($stmt,$fetch_mode);
            $stmt->close();
        }
        else {
            $result = $mysqli->query($sql);
            if($mysqli->errno){
                Doggy_Log_Helper::error("SQL ERROR:".$mysqli->error.' SQL:'.$sql);
                throw new Doggy_Db_Exception('SQL ERROR:'.$mysqli->error);
            }
            $rows = $this->_fetch_query_result($result,$fetch_mode);
            $result->close();
        }
        return $rows;
    }

    private function _fetch_stmt_result($stmt,$fetch_mode){
        $rows = array();
        if ($this->enable_mysqlnd) {
            $result = $stmt->get_result();
            $rows = $result->fetch_all($fetch_mode);
        }
        else {
            while($row = $this->_stmt_fetch_array($stmt)){
                $rows[] = $row;
            }
        }
        $stmt->free_result();
        return $rows;
    }
    
    private function _fetch_query_result($result,$fetch_mode){
        $rows = array();
        if ($this->enable_mysqlnd) {
            $rows = $result->fetch_all($fetch_mode);
        }
        else {
            while($row = $result->fetch_array($fetch_mode)){
                $rows[] = $row;
            }
        }
        return $rows;
    }
    
    private function _stmt_fetch_array($stmt){
        $results = array();
        if (!$this->stmt_var_bound){
            $meta = $stmt->result_metadata();
            while ($column = $meta->fetch_field()) {
                // this is to stop a syntax error if a column name has a space in
                // e.g. "This Column". 'Typer85 at gmail dot com' pointed this out
                $column_name = str_replace(' ', '_', $column->name);
                $bind_vars[] = &$this->stmt_results[$column_name];
            }
            call_user_func_array(array($stmt, 'bind_result'), $bind_vars);
            $this->stmt_var_bound = true;
        }
        
        if ($stmt->fetch() != null) {
           // this is a hack. The problem is that the array $stmt->results is full 
           // of references not actual data, therefore when doing the following:
           // while ($row = $this->stmt->fetch_assoc()) {
           // $results[] = $row;
           // }
           // $results[0], $results[1], etc, were all references and pointed to 
           // the last dataset
           foreach ($this->stmt_results as $k => $v) {
               $results[$k] = $v;
           }
           return $results;
       } else {
           //free bound results
           $this->stmt_results = array();
           $this->stmt_var_bound = false;
           return null;
       }
        
    }

    protected function fetch_result_rows($result,$fetch_mode=MYSQLI_ASSOC){
        $rows=array();
        while($row = $result->fetch_array($fetch_mode)){
            $rows[]=$row;
        }
        $result->close();
        return $rows;
    }
    public function get_tables(){
        if(!$this->connect()) return array();
        $mysqli = $this->mysqli;
        
        $rows = $this->query(self::$_metaTablesSQL,-1,-1,array(),MYSQLI_NUM);
        $tables=array();
        for ($i=0; $i < count($rows); $i++) { 
            $tables[]=$rows[$i][0];
        }
        return $tables;
    }

    /**
     * fetch all fields meta
     */
    public function get_fields($table){
        if(!$this->connect()) return array();
        $sql = sprintf(self::$_metaColumnSQL,$table);
        $fieldObjs = $this->query($sql);
        $fields = array();
        foreach($fieldObjs as $f){
            $type = $f['Type'];
            if (preg_match("/^(.+)\((\d+),(\d+)/", $type, $query_array)) {
				$fld_type = $query_array[1];
				$fld_max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
			} elseif (preg_match("/^(.+)\((\d+)/", $type, $query_array)) {
				$fld_type = $query_array[1];
				$fld_max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
			} elseif (preg_match("/^(enum)\((.*)\)$/i", $type, $query_array)) {
				$fld_type = $query_array[1];
				$fld_max_length = max(array_map("strlen",explode(",",$query_array[2]))) - 2; // PHP >= 4.0.6
				$fld_max_length = ($fld->max_length == 0 ? 1 : $fld->max_length);
			} else {
				$fld_type = $type;
				$fld_max_length = -1;
			}
            $name = $f['Field'];
            $fields[$name]= array(
                'name'  => $f['Field'],
                'type'  => self::convert_field_type($fld_type),
                'size'  => $fld_max_length,
                'pk'        => (bool)(strtolower($f['Key']) == 'pri'),
                'default'   => $f['Default'],
                'null'   => (bool) (strtolower($f['Null']) == 'yes'),
                'auto_inc'  => (bool)(strtolower($f['Extra']) == 'auto_increment'),
            );
        }
        return $fields;
    }
    private static function convert_field_type($type) {
        switch($type){
            case 'varchar':
            case 'char':
            case 'text':
                return 'S';
            case 'date':
                return 'D';
            case 'datetime':
                return 'T';
            case 'time':
            case 'int':
            case 'float':
            case 'long':
                return 'N';
            default:
                return 'S';
        }
    }
    
    public function gen_seq($name){
        $name = 'SEQ_'.strtoupper($name);
        if(!$this->connect()){
            throw new Doggy_Db_Exception('Connection failed.');
        }
        
        $ok=false;
        
        $next_sql = sprintf(self::$_genIDSQL,$name);
        try {
            $this->execute($next_sql);
            $ok=true;
        } catch (Doggy_Db_Exception $e) {
        }
        if (!$ok) {
            try {
                $this->execute(sprintf(self::$_genSeqSQL,$name));
	            $this->execute(sprintf(self::$_genSeq2SQL,$name,0));
                $this->execute($next_sql);
                $ok=true;
            } catch (Doggy_Db_Exception $e) {
                Doggy_Log_Helper::error("Cannot gen sequence,cause:".$e->getMessage());
                throw new Doggy_Db_Exception('Generate sequence failed.');
            }
        }
        //retrieve sequenece current value
        if ($ok) {
            $v = $this->query(self::$_getSeqSQL);
            $id = $v[0]['id'];
        }
        return $id;
    }
    /**
     * drop a sequenece 
     *
     * @return boolean
     **/
    public function drop_seq($name){
        $name = 'SEQ_'.strtoupper($name);
        return $this->execute('DROP TABLE IF EXISTS '.$name);
    }
    
    
}
// vim:ts=4 et tw=4
?>