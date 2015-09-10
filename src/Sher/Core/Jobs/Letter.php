<?php
/**
 * 群发私信服务
 * @author tianshuai
 */
class Sher_Core_Jobs_Letter extends Doggy_Object {
	
	/**
	 * Before perform
	 * Set up environment for the job
	 */
	public function setUp(){}
	
	/**
	 * Run job
	 */
	public function perform(){
		Doggy_Log_Helper::debug("Make letter task jobs!");
		$edm_id = $this->args['edm_id'];
		
		try{
			if(empty($edm_id)){
				Doggy_Log_Helper::warn("Waiting edm_id is empty!");
				return false;
			}
			// 检测私信列表是否存在
			$model = new Sher_Core_Model_Emd();
			$result = $model->load((int)$edm_id);
			if(empty($result)){
				Doggy_Log_Helper::warn("Waiting letter is empty!");
				return false;
			}
			if($result['state'] != Sher_Core_Model_Edm::STATE_WAITING){
				Doggy_Log_Helper::warn("Letter isnot waiting!");
				return false;
			}
			
			// 设置为正在发送
			$model->mark_set_send($edm_id);
			
			// 形成发送队列
			$page = 1;
			$size = 1000;
			// 发送数量
			$send_count = 0;
			$is_end = false;
			$next_id = '';
			
			$user_model = new Sher_Core_Model_User();
			while(!$is_end){
				$query = array();
        $query['kind'] = array('$ne'=>9);
				if(!empty($next_id)){
					$query = array(
						'_id' => array('$gt' => $next_id)
					);
				}
				$options = array(
					'page' => $page,
					'size' => $size,
					'sort' => array('_id' => 1),
				);
				$rows = $user_model->find($query, $options);
				if(empty($rows)){
					$is_end = true;
					Doggy_Log_Helper::warn("Waiting letter is end!");
					break;
				}
				$max = count($rows);
				for($i=0;$i<$max;$i++){
					$next_id = $rows[$i]['_id'];
					
					Doggy_Log_Helper::warn("sending letter end next_id: ".$next_id);
          echo "00000000000\n";
					
					// 记录发送次数
					$send_count += 1;
				}
				
				if($max < $size){
					$is_end = true;
					Doggy_Log_Helper::warn("Waiting letter is end!");
					
					// 设置完成状态
					$model->mark_set_finish($edm_id);
					
					break;
				}
				
				$page += 1;
			}
			
			unset($user_model);
			unset($model);
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Queue letter failed: ".$e->getMessage());
		}
		
	}
	
	/**
	 * After perform
	 * Remov environment for this job
	 */
	public function tearDown(){}
	
}
?>
