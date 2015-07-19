<?php
/**
 * 投票 Model
 * @author caowei@taihuoniao.com
 */
class Sher_Core_Model_Vote extends Sher_Core_Model_Base {

    protected $collection = "vote";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 所属分类
	const TYPE_TOPIC = 1; // 代表话题
	
    protected $schema = array(

		# 投票名称
		'title' => '',
        # 所属分类
        'type' => self::TYPE_TOPIC,
        # 关联项目id
        'relate_id' => null,
        # 问题id
        'problem_ids' => array(),
        # 关联用户id
        'user_id' => 0,
		# 是否启用
		'status' => 1,
    );
	
	protected $required_fields = array('title');
	protected $int_fields = array('user_id');
	protected $float_fields = array();
	protected $counter_fields = array();
	protected $retrieve_fields = array();
    
	protected $joins = array(
	    
	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
        $row['view_url'] = Sher_Core_Helper_Url::topic_view_url($row['relate_id']);
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
	 * 关联查询投票相关信息
	 */
    public function find_votes($id = 0){
        
		if(!empty($id)){
                
			// 查询投票信息
			$vote = $this->find_by_id((int)$id);
			
			// 查询问题信息
			$model_problem = new Sher_Core_Model_Problem();
			$problem = $model_problem->find(array('vote_id'=>(int)$id));
			
			// 查询答案信息
			$model_answer = new Sher_Core_Model_Answer();
			foreach($problem as $k => $v){
				$answer = $model_answer->find(array('problem_id'=>$v['_id']));
				$problem[$k]['answer'] = $answer;
			}
			$vote['problem'] = $problem;
			
			return $vote;
		}
    }
	
	/**
	 *  关联删除数据
	 */
	public function vote_remove($id){
		
		// 1. 查询答案信息
		$pro_model = new Sher_Core_Model_Problem();
		$result = $pro_model->find(array("vote_id"=>(int)$id));
		
		// 2. 删除答案信息
		$is_ok = 0;
		$ans_model = new Sher_Core_Model_Answer();
		foreach($result as $v){
			$answer_id = $v['_id'];
			if(!$ans_model->remove(array('problem_id' => $answer_id))){
				$is_ok++;
			}
		}
		unset($ans_model);
		
		// 3. 删除问题信息
		if(!$is_ok){
			$is_del = 0;
			foreach($result as $v){
				$pro_id = $v['vote_id'];
				if(!$pro_model->remove(array('vote_id' => (int)$pro_id))){
					$is_del++;
				}
			}
			unset($pro_model);
			
			// 4. 删除投票信息
			if(!$is_del){
				return $this->remove(array("_id"=>(int)$id)); 
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}
