<?php
/**
 * Simple class implements  data mapper pattern.
 */
class DoggyX_Model_Mapper {

    private static $_cache;

    public static function &load_model($id,$model_class,$some_fields=array()) {
        if (is_object($model_class)) {
            $model = $model_class;
            $model_class = get_class($model);
        }
        if (!isset(self::$_cache[$model_class]["$id"])) {
            if (!isset($model)) {
                $model = new $model_class();
            }
			// 返回字段
			$default_fields = $model->get_retrieve_fields;
			$retrieve_fields = !empty($some_fields) ? $some_fields : $default_fields;
            $row = $model->find_by_id($id, $retrieve_fields);
            if (!empty($row)) {
                $row = $model->extended_model_row($row);
                self::$_cache[$model_class]["$id"] = $row;
                $obj = & self::$_cache[$model_class]["$id"];
            }
            else {
                $obj = null;
            }
        }
        else {
            $obj = & self::$_cache[$model_class]["$id"];
        }
        return $obj;
    }

    public static function &load_model_list($id_list,$model_class,$some_fields=array()) {
        $result = array();
        for ($i=0; $i < count($id_list); $i++) {
            $id = is_array($id_list[$i])?$id_list[$i]['_id']:$id_list[$i];
            $result[$i] = & self::load_model($id,$model_class,$some_fields);
        }
        return $result;
    }

    public static function update_model($id,$model_class,$new_data) {
        self::$_cache[$model_class][$id] = $new_data;
    }

    public static function remove_model($id,$model_class) {
        unset(self::$_cache[$model_class][$id]);
    }
}