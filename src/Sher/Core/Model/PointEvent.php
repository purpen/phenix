<?php
/**
 * 积分事件定义表
 *
 */
class Sher_Core_Model_PointEvent extends Sher_Core_Model_Base {
    protected $collection = 'points.event';

    protected $schema = array(
        'name' => null,
        'code' => null,
//      周期限定
        'daily_limit' => 0,
        'month_limit' => 0,
        # 奖励
        'award_point_type' =>  null,
        'award_point_amount' => 0,
    );
    protected $joins = array(
    );
    protected $required_fields = array('name', 'code');
    protected $ini_fields = array('daily_limit', 'month_limit');

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
        if ($this->insert_mode) {
            $code = $this->data['code'];
            if ($this->count(array('code' => $code))) {
                throw new Doggy_Model_ValidateException("code:$code not unique");
            }
        }
        return true;
    }
}
