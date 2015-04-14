<?php
/**
 * 积分事件定义表
 *
 */
class Sher_Core_Model_PointEvent extends Sher_Core_Model_Base {
    protected $collection = 'points.event';

    protected $mongo_id_style = self::MONGO_ID_CUSTOM;
    protected $schema = array(
        'name' => null,
        # 日积分上限
        'daily_limit' => 0,
        # 月积分上限
        'month_limit' => 0,
        # 奖励积分
        'point_type' =>  null,
        'point_amount' => 0,
        'note' => null,
    );
    protected $joins = array(
    );
    protected $required_fields = array('name');
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
            $code = $this->data['_id'];
            if ($this->count(array('_id' => $code))) {
                throw new Doggy_Model_ValidateException("code:$code not unique");
            }
        }
        return true;
    }
}
