<?php
/**
 * 专家评估
 * @author purpen
 */
class Sher_Core_Model_Assess extends Sher_Core_Model_Base  {

    protected $collection = "assess";
	
    protected $schema = array(
    	'user_id' => null,
    	'target_id' => null,
		'score' => array(
			# 可用性
			'usability' => 0,
			# 外观设计
			'design' => 0,
			# 创意性
			'creativity' => 0,
			# 功能性
			'content' => 0,
		),
		'average' => 0,
    );
	
    protected $joins = array();
	
    protected $required_fields = array('user_id', 'target_id');
    protected $int_fields = array('user_id', 'target_id');
	
	/**
	 * 扩展关联数据
	 */
    protected function extra_extend_model_row(&$row) {}
	
	
	/**
	 * 保存之前
	 */
	protected function before_save(&$data) {
		// 转换成整数
		$data['score']['usability'] = (int)$data['score']['usability'];
		$data['score']['design'] = (int)$data['score']['design'];
		$data['score']['creativity'] = (int)$data['score']['creativity'];
		$data['score']['content'] = (int)$data['score']['content'];
		
		$average = ($data['score']['usability'] + $data['score']['design'] + $data['score']['creativity'] + $data['score']['content'])/4;
		// 保留2位小数
		$data['average'] = round($average ,2);
	
	    parent::before_save($data);
	}

	/**
	 * 
	 */	
	protected function after_save() {
		$this->calculate_total_point();
	}
	
	/**
	 * 计算平均分
	 */
	protected function calculate_total_point(){
		$target_id = $this->data['target_id'];
		
		$result = $this->find(array('target_id'=>$target_id));
		
		if (!empty($result)){
			$count = count($result);
			$sum = array(
				'usability' => 0,
				'design' => 0,
				'creativity' => 0,
				'content' => 0,
			);
			$score = array();
			for($i=0;$i<$count;$i++){
				$sum['usability'] += $result[$i]['score']['usability'];
				$sum['design'] += $result[$i]['score']['design'];
				$sum['creativity'] += $result[$i]['score']['creativity'];
				$sum['content'] += $result[$i]['score']['content'];
			}
			// 计算平均分
			$score['usability'] = round($sum['usability']/$count, 2);
			$score['design'] = round($sum['design']/$count, 2);
			$score['creativity'] = round($sum['creativity']/$count, 2);
			$score['content'] = round($sum['content']/$count, 2);
		
			$score_average = round(($score['usability'] + $score['design'] + $score['creativity'] + $score['content'])/4, 2);
		
			$product = new Sher_Core_Model_Product();
			$product->update_expert_score($target_id, $score, $count, $score_average);
		}
		
	}
	
}
?>