<?php
/**
 * model层核心
 */
class Sher_Core_Model_Base extends DoggyX_Model_Lite {

    /**
     * One-One mapping of model relation
     *
     *  $joins = array('joined_attribute_name' => array( fk => 'related model class') );
     *
     * @var array
     */
    protected $joins = array();
	protected $int_fields = array();
}
?>