<?php
/**
 * 积分余额和配量表
 * 
 */
class Sher_Core_Model_UserPointQuota extends Sher_Core_Model_Base {
    protected $collection = 'points.quota';
    
    protected $schema = array(
        //user_id
        '_id' => null,
        //积分日结控制表, d+日期为键值, d20150101
        // daily_point_limit => array(
        //     d20150301 => array(
        //          evt_login => array(exp => 50),
        //),
        //  ),
        //
        'daily_point_limit' => array(
            'd20150101' => null,
        ),
        //积分日结控制表, m+月份为键值, 如m201501
        'month_point_limit' => array(
            'm20150101' => null,
        ),
    );
    protected $joins = array(
        'user_rank' => array('rank_id' => 'Sher_Core_Model_UserRankDefine'),
    );
    protected $required_fields = array();
    protected $ini_fields = array('rank_id');

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

    public function init_record($user_id) {
        return $this->create(array('_id' => $user_id));
    }

    public function touch_daily_quota($d, $evt, $point_type, $touch_month=true){
        $spec = array('_id' => $this->id);
        $key = 'daily_point_limit.d'.$d.'.'.$evt.'.'.$point_type;
        $this->inc($spec, $key);
        if ($touch_month) {
            $m = substr($d, 0, 6);
            $this->touch_month_quota($m, $evt, $point_type);
        }
    }

    public function touch_month_quota($m, $evt, $point_type) {
        $spec = array('_id' => $this->id);
        $key = 'month_point_limit.m'.$m.'.'.$evt.'.'.$point_type;
        $this->inc($spec, $key);
    }
}