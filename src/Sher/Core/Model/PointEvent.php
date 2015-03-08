<?php
/**
 * 积分事件定义表
 *
 */
class Sher_Core_Model_PointEvent extends Sher_Core_Model_Base {
    protected $collection = 'points.event';

    protected $schema = array(
        'evt_code' => null,
        'evt_group' => null,
//      周期限定
        'freq_constraint' => array(
//            每日上限
            'daily' => 0,
//            每月上限
            'month' => 0,
//            特定天数
            'period' => array('days' => 20, 'max' => 0)
        ),
//        积分限定
        'point_constraint' => array(
            'daily' => array(
                # point_type => max_val
            ),
            'month' => array(),
            'period' => array(
                array(
//                    'point_type' => array( 'days'=> 3, 'max' => 0),
                )
            )
        ),
        # 奖励
        'award' =>  array(
            'point_type' => null,
            'point_val' => 0,
        )
    );
    protected $joins = array(
    );
    protected $required_fields = array();
    protected $ini_fields = array();

    // protected $auto_update_timestamp = true;
    // protected $created_timestamp_fields = array('created_on');
    // protected $updated_timestamp_fields = array('updated_on');

    protected function extra_extend_model_row(&$row) {
    }

    //~ some event handles
    protected function before_save(&$data) {
    }
    protected function after_save() {
    }
    protected function validate() {
        return true;
    }
}
