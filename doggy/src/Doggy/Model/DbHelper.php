<?php
class Doggy_Model_DbHelper {

    /**
     * Returns model's db instance
     *
     * @param string $model_name 
     * @return Doggy_Db_Driver
     */
    public static function get_model_db($model_name) {
        $id = strtolower($model_name);
        if (isset(Doggy_Config::$vars['app.model.'.$id.'.db'])) {
            $key = Doggy_Config::$vars['app.model.'.$id.'.db'];
        }
        else {
            $key = 'default';
        }
        return Doggy_Db_Manager::get_db($key);
    }
    
    /**
     * 删除多对多关联表中的匹配外键的记录
     *
     * @param string $db 
     * @param string $table 
     * @param string $foreign_key 
     * @param string $foreign_value 
     * @param string $condition 
     * @param string $vars 
     * @return void
     */
    public static function delete_link_table_data($db,$table,$foreign_key,$foreign_value,$condition=null,$vars=array()){
        $condition = empty($condition)?"$foreign_key=?":$condition." AND $foreign_key=?";
        $sql = "DELETE FROM $table WHERE $condition";
        $vars[]= $foreign_value;
        try{
            $db->execute($sql,$vars);
        }catch(Doggy_Dba_Exception $e){
            Doggy_Log_Helper::error('delete link table data failed,db error:'.$e->getMessage());
            throw new Doggy_Model_Exception('delete link table data failed,db error:'.$e->getMessage());
        }
    }

    /**
     * 更新多对多关联表记录
     *
     * 本方法删除当前model在关联表中的所有记录，然后再插入新的关联记录。
     * 
     * @param string $db 
     * @param string $this_foreign_key 
     * @param string $other_foreign_key 
     * @param string $table 
     * @param string $rows 
     * @param string $other_fields 
     * @return void
     */ 
    public static function save_link_table_data($db,$this_foreign_key,$other_foreign_key,$table,$rows,$other_fields=array()){ 
        foreach($rows as $row) {
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
            $sql .= 'VALUES('.self::build_vars_hoder(count($fields)).')';
            try{
                $db->execute($sql,$vars);
            }catch(Doggy_Db_Exception $e){
                Doggy_Log_Helper::error('Save habm data failed,dba error:'.$e->getMessage());
                throw new Doggy_Model_Exception('Save habm data failed,dba error:'.$e->getMessage());
            }
        }
        
    }
    
    /**
     * Returns a the name of the join table that would be used for the two
     * tables. The join table name is decided from the alphabetical order
     * of the two tables.  e.g. "genres_movies" because "g" comes before "m"
     *
     * @param string $first 
     * @param string $second 
     * @param string $prefix 
     * @return string
     */ 
    public static function build_link_table_name($first, $second,$prefix=NULL) {
        $tables = array();
        $tables["one"] = $first;
        $tables["many"] = $second;
        @asort($tables);
        $link_table_name = @implode("_", $tables);
        return empty($prefix)? $link_table_name:$prefix.'_'.$link_table_name;
    }
}
?>