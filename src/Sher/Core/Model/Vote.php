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
        'relate_id' => 0,
		# 是否启用
		'status' => 1,
		# 统计数量
		'nums' => 0,
    );
	
	protected $required_fields = array('title','relate_id');
	protected $int_fields = array('relate_id','nums');
	protected $float_fields = array();
	protected $counter_fields = array('nums');
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
    protected function after_save(){
        parent::after_save();
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
				$answer = $model_answer->find(array('problem_id'=>(string)$v['_id']));
				foreach($answer as $key => $val){
					$answer[$key]['_id'] = (string)$val['_id'];
				}
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
			$pro_id = $v['_id'];
			$answer_rows = $ans_model->find(array('problem_id' => (string)$pro_id));
			foreach($answer_rows as $val){
				$ans_id = $val['_id'];
				if(!$ans_model->remove(array('_id' => $ans_id))){
					$is_ok++;
				}
			}
		}
		unset($ans_model);
		
		// 3. 删除问题信息
		if(!$is_ok){
			$is_del = 0;
			foreach($result as $v){
				$pro_id = $v['_id'];
				if(!$pro_model->remove(array('_id' => $pro_id))){
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
	
	/**
	 * 增加计数
	 */
	public function inc_counter($field_name = 'nums', $id = 0, $inc = 1){
		if(!$id){
			$id = $this->id;
		}
		
		if(empty($id) || !in_array($field_name, $this->counter_fields)){
			return false;
		}
		
		return $this->inc($id, $field_name, $inc);
	}
	
	/**
	* 投票统计
	*/
	public function statistics($id){
		$vote = $this->find_votes((int)$id);
		//return $vote;
		foreach($vote['problem'] as $k=>$v){
		$nums = 0;
		//求每个问题下投票总数,计算百分比
			foreach($v['answer'] as $key=>$val){
				$nums += (int)$val['nums'];
			}
			foreach($v['answer'] as $kl=>$vl){
				$vote['problem'][$k]['answer'][$kl]['nums_rate'] = (int)(((float)$vl['nums']/$nums) * 100);
			}
		}

		return $vote;
	}
}
