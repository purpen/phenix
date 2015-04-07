<?php
/**
 * 用户积分实时余额
 * 
 */
class Sher_Core_Model_UserPointBalance extends Sher_Core_Model_Base {
    protected $collection = 'points.balance';
    
    protected $schema = array(
        '_id' => null,
        // 各个积分的实时余额
        'balance' => array(
            'exp' => 0,
            'money' => 0,
        ),
        // 冻结交易中的余额
        'pending' => array(
            'exp' => 0,
            'money' => 0,
        ),
        // 处理中的交易记录
        'trans' => array(),
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

    public function add_out_trans($val, $point_type, $trans_record_id) {
        $spec = array('_id' => $this->id);
        $balance_key = 'balance.'.$point_type;
        $pending_key = 'pending.'.$point_type;
        $spec[$balance_key] = array('$gte' => $val);
        $spec['trans'] = array('$ne' => $trans_record_id);
        $doc = array(
            '$inc' => array($balance_key => $val * -1, $pending_key => $val),
            '$addToSet' => array('trans' => $trans_record_id),
        );
        return self::$_db->find_and_modify($this->collection, array(
           'query' => $spec,
           'update' => $doc,
           'new' => true,
        ));
    }

    public function add_in_trans($val, $point_type, $trans_record_id) {
        $spec = array(
            '_id' => $this->id,
            'trans' => array('$ne'=>$trans_record_id),
        );
        $pending_key = 'pending.'.$point_type;
        $doc = array(
            '$inc' => array($pending_key => $val),
            '$addToSet' => array('trans' => $trans_record_id),
        );
        return self::$_db->find_and_modify($this->collection, array(
            'query' => $spec,
            'update' => $doc,
            'new' => true,
        ));
    }

    public function commit_in_trans($val, $point_type, $trans_record_id) {
        $spec = array(
            '_id' => $this->id,
            'trans' => $trans_record_id,
        );
        $pending_key = 'pending.'.$point_type;
        $balance_key = 'balance.'.$point_type;
        $doc = array(
            '$inc' => array($balance_key => $val, $pending_key=> $val * -1),
            '$pull' => array('trans' => $trans_record_id),
        );
        return self::$_db->find_and_modify($this->collection, array(
            'query' => $spec,
            'update' => $doc,
            'new' => true,
        ));
    }

    public function commit_out_trans($val, $point_type, $trans_record_id) {
        $spec = array(
            '_id' => $this->id,
            'trans' => $trans_record_id,
        );
        $pending_key = 'pending.'.$point_type;
        $doc = array(
            '$inc' => array($pending_key=> $val * -1),
            '$pull' => array('trans' => $trans_record_id),
        );
        return self::$_db->find_and_modify($this->collection, array(
            'query' => $spec,
            'update' => $doc,
            'new' => true,
        ));
    }

    public function touch_init_record($user_id, $point_types=array('exp','money')) {
        $init_values = array(
            '_id' => $user_id,
        );
        foreach ($point_types as $code) {
            $init_values['balance'][$code] = 0.0;
            $init_values['pending'][$code] = 0.0;
        }
        return $this->create($init_values);
    }
}