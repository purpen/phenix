<?php
/**
 * 投票记录 Model
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Model_VoteRecord extends Sher_Core_Model_Base {

    protected $collection = "vote_record";
	
    protected $schema = array(
        
		# 投票id
        'vote_id' => 0,
		# 关联用户id
        'user_id' => 0,
		# 关联项目id
        'relate_id' => 0,
		
        # 问题id
        'problem_id' => null,
		# 答案id
        'answer_id' => null,
    );
	
	protected $required_fields = array('vote_id','problem_id','answer_id',);
	protected $int_fields = array('vote_id','user_id','relate_id');
	protected $float_fields = array();
	protected $counter_fields = array();
	protected $retrieve_fields = array();
    
	protected $joins = array(
	    
	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        
	}
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    parent::before_save($data);
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save($data){
        parent::after_save($data);
    }
}
