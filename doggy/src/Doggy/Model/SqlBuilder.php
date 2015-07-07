<?php
class Doggy_Model_SqlBuilder {
    /**
     * Resutrn ? holders
     * @param int $size
     * @return string
     */
    public static function build_vars_hoder($size){
        $holds= array_pad(array(),$size,'?');
        return implode(',',$holds);
    }

    /**
     * 根据选项构建SQL串
     *
     * @param array $options
     * @return string
     * @access protected
     */
    public static function build_sql_options($options=array()){
       $condition=null;
       $order_by=null;
       $joins=null;
       $fields=null;
       $group_by=null;
       $table = null;
       extract($options,EXTR_IF_EXISTS);
       $sql ='SELECT ';
       $sql.=  $fields? "$fields ":'* ';
       $sql.= " FROM ".$table;
       
       if($joins){
        $sql .= " $joins ";
       }
       $sql.= !empty($condition)? " WHERE $condition ":'';
       $sql.= $group_by?" GROUP BY $group_by":'';
       $sql.= $order_by? " ORDER BY $order_by ":'';
       return trim($sql);
    }
    
    public static function build_create_sql($table_name,$fields,$data,&$vars) {
        $sql = "INSERT INTO ".$table_name;
        foreach ($fields as $k => $v) {
            if(!isset($data[$k])) continue;
            $columns[] = $k;
            $vars[] = $data[$k];
            $holders[] = '?';
        }
        $sql.= ' ('.implode(', ',$columns).') VALUES ('.implode(', ',$holders).') ';
        return $sql;
    }
    
    
    public static function build_pk_where($pk_name,$pk_value) {
        if (is_array($pk_value)) {
            $sql = $pk_name . ' IN ('.self::build_vars_hoder(count($pk_value)).')';
        }
        else {
            $sql = $pk_name.' = ?';
        }
        return $sql;
    }
    
    
    public static function build_update_sql($table_name,$fields,$data, $pk_name,&$vars) {
        $sql = "UPDATE ".$table_name;
        foreach ($fields as $k => $v) {
            if(!isset($data[$k])) continue;
            $pairs[] = " $k = ?";
            $vars[] = $data[$k];
        }
        $sql .= ' SET '.implode(', ',$pairs).' WHERE ';
        $sql .= self::build_pk_where($pk_name,$data[$pk_name]);
        $vars[] = $data[$pk_name];
        return $sql;
    }
}
?>