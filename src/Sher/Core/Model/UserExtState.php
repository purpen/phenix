<?php
/**
 * 会员扩展状态表
 * 
 */
class Sher_Core_Model_UserExtState extends Sher_Core_Model_Base {
    protected $collection = 'user.ext_state';
    
    protected $schema = array(
        '_id' => null,
        // 等级状态
        'rank_id' => 1,
        'next_rank_id' => 2,
        // 当前等级积分
        'rank_point' => 0,
        // 升级所需等级积分
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
        $user_rank = $row['user_rank'];
        if (empty($user_rank)) {
            return;
        }
        $total_point = isset($user_rank['point_amount'])?$user_rank['point_amount']: 0;
        if (empty($total_point)) {
            return;
        }
        $percent = round($row['rank_point'] / $total_point * 100, 2);
        $row['upgrade_percent'] = $percent;
    }
    
    //~ some event handles
    protected function before_save(&$data) {
    }
    protected function after_save() {
    }
    protected function validate() {
        return true;
    }

    public function init_record($user_id){
        return $this->create(array('_id' => $user_id));
    }
}