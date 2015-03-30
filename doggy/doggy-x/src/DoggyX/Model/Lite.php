<?php
/**
 * Lite ORM, a very simple/lightweight ORM class.
 * 
 * 在DoggyX_Model_Mongo_Base上实现一个轻量的ROM
 * backport from live project, xiaba/lgk ...
 */
class DoggyX_Model_Lite extends DoggyX_Model_Mongo_Base {
    /**
     * Simple orm definition. (One-One, One-Many)
     *
     *  $joins = array('joined_attribute_name' => array( fk => 'related model class') );
     *
     * some live example from xiaba:
     *
     * Albuma: (XB_Core_Model_Album.php)
     * protected $joins = array(
     *    'user' => array('user_id' => 'XB_Core_Model_User'),
     *    'stuff_list' => array('stuffs' => 'XB_Core_Model_Stuff'),
     *    'cover_stuff' => array('cover_id' => 'XB_Core_Model_Stuff'),
     * );
     *
     * Now, after extend_load,
     * $album_row = $album->extend_load('xxxxxx');
     * or
     * $album_row = DoggyX_Model_Mapper::load_model('xxxx','XB_Core_Model_Album');
     * 
     * $album_row.user is  user array
     * $album_row.stuff_list is List of Stuff rows.
     *
     * @var array
     */
    protected $joins = array();
    
	/**
	 * Fields of the results to return
	 */
	protected $retrieve_fields = array();
    
    /**
     * Expand load model relations and other attributes
     *
     * @param array $row
     * @return array
     */
    public function extended_model_row($row) {
        if (empty($row) || isset($row['__extend__'])) {
            return $row;
        }
        $row = $this->load_joins($row);
        $this->extra_extend_model_row($row);
        $row['__extend__'] = true;
        return $row;
    }

    /**
     * Extra information need to load for given model row.
     *
     * You can implement some virtual fields here.
     *
     * @param array $row
     * @return void
     */
    protected function extra_extend_model_row(&$row) {
    }

    /**
     * Load a Full model data row, include all relations and extends attributes.
     *
     * @param string $id
     * @return array
     */
    public function &extend_load($id=null) {
        if (is_null($id)) {
            $id = $this->id;
        }
        return DoggyX_Model_Mapper::load_model($id,$this);
    }

    /**
     * Load full information model from given model id list.
     *
     * @param array $id_list
     * @return array
     */
    public function extend_load_all($id_list,$some_fields=array()) {
        return DoggyX_Model_Mapper::load_model_list($id_list,$this,$some_fields);
    }

    /**
     * Load this model's joined models into given data row.
     *
     * @param string $row
     * @return array
     */
    public function load_joins($row) {
        if (empty($this->joins) || empty($row)) {
            return $row;
        }
        foreach ($this->joins as $attribute => $definition) {
            list($pk_name,$model_class) = each($definition);
            if (isset($row[$pk_name])) {
                if (is_array($row[$pk_name])) {
                    $row[$attribute] = & DoggyX_Model_Mapper::load_model_list($row[$pk_name],$model_class);
                }
                else {
                    $row[$attribute] = & DoggyX_Model_Mapper::load_model($row[$pk_name],$model_class);
                }
            }
        }
        return $row;
    }
    
	/**
	 * Get retrieve_fields
	 */
	public function get_retrieve_fields(){
		return $this->retrieve_fields;
	}
    
}
