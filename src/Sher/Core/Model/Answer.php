<?php
/**
 * 投票->问题->答案 Model
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Model_Answer extends Sher_Core_Model_Base {

    protected $collection = "answer";
	
    protected $schema = array(
	
		# 问题名称
		'title' => '',
        # 问题id
        'problem_id' => null,
		# 统计数量
		'nums' => 0,
    );
	
	protected $required_fields = array('title');
	protected $int_fields = array('nums');
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
	
	/**
	 *  关联删除数据
	 */
	public function answer_remove($id){
		return $this->remove(array("_id"=>$id)); 
	}
}
