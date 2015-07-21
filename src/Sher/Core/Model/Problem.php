<?php
/**
 * 投票->问题 Model
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Model_Problem extends Sher_Core_Model_Base {

    protected $collection = "problem";
	
	const SELECT_TYPE = 1; // 1表示单选 2表示多选
	
    protected $schema = array(

		# 问题名称
		'title' => '',
        # 投票id
        'vote_id' => 0,
		# 问题类型
		'select_type' => self::SELECT_TYPE,
    );
	
	protected $required_fields = array('title','vote_id');
	protected $int_fields = array('select_type','vote_id');
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
    protected function after_save(){
        parent::after_save();
    }
	
	/**
	 *  关联删除数据
	 */
	public function problem_remove($id){
		
		if($id){
			$is_del = 0;
			$ans_model = new Sher_Core_Model_Answer();
			// 1. 删除答案信息
			$answer_rows = $ans_model->find(array('problem_id' => (string)$id));
			foreach($answer_rows as $val){
				$ans_id = $val['_id'];
				if(!$ans_model->remove(array('_id' => $ans_id))){
					$is_ok++;
				}
			}
			unset($ans_model);
			
			// 2. 删除投票信息
			if(!$is_del){
				return $this->remove($id); 
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}
