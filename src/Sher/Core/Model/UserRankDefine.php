<?php
/**
 * 会员等级定义表
 * 
 */
class Sher_Core_Model_UserRankDefine extends Sher_Core_Model_Base {
    protected $collection = 'points.user_rank_define';

    protected $mongo_id_style = self::MONGO_ID_SEQ;

    protected $schema = array(
        // 等级ID
        'rank_id' => 1,
        //下一等级的ID
        'next_rank_id' => null,
        // 会员头衔
        'title' => null,
        //头衔说明
        'note' => null,
        //升级需要的积分类型
        'point_type' => null,
        //升级需要的积分数额
        'point_amount' => 0,
        //升级获得奖励的积分
        'award_point_type' => null,
        'award_point_amount' => 0,
    );
    protected $joins = array(
        'next_rank' => array('next_rank_id' => 'Sher_Core_Model_UserRankDefine'),
    );
    protected $required_fields = array();
    protected $int_fields = array('rank_id', 'next_rank_id', '_id');

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
            $code = (int) $this->data['rank_id'];
            if ($this->count(array('rank_id' => $code))) {
                throw new Doggy_Model_ValidateException("rank_id:$code not unique");
            }
        }
        return true;
    }


    public function set_rank_id_seq($val) {
        return $this->set_seq_val($this->seq_name, $val);
    }
}