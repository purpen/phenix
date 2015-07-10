<?php
/**
 * 邮件服务
 * @author purpen
 */
class Sher_Core_Jobs_Edm extends Doggy_Object {
	
	/**
	 * Before perform
	 * Set up environment for the job
	 */
	public function setUp(){}
	
	/**
	 * Run job
	 */
	public function perform(){
		Doggy_Log_Helper::debug("Make edm task jobs!");
		$edm_id = $this->args['edm_id'];
		
		try{
			if(empty($edm_id)){
				Doggy_Log_Helper::warn("Waiting edm_id is empty!");
				return false;
			}
			// 检测邮件是否存在
			$model = new Sher_Core_Model_Edm();
			$result = $model->load($edm_id);
			if(empty($result)){
				Doggy_Log_Helper::warn("Waiting edm is empty!");
				return false;
			}
			if($result['state'] != Sher_Core_Model_Edm::STATE_WAITING){
				Doggy_Log_Helper::warn("Edm isnot waiting!");
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
			
			$emailing = new Sher_Core_Model_Emailing();
			while(!$is_end){
				$query = array();
				if(!empty($next_id)){
					$query = array(
						'_id' => array('$gt' => $next_id)
					);
				}
				$options = array(
					'page' => $page,
					'size' => $size,
					'sort' => array('created_on' => 1)
				);
				$rows = $emailing->find($query, $options);
				if(empty($rows)){
					$is_end = true;
					Doggy_Log_Helper::warn("Waiting edm is end!");
					break;
				}
				$max = count($rows);
				for($i=0;$i<$max;$i++){
					$next_id = $rows[$i]['_id'];
					
					$args = array(
						'edm_id' => $edm_id,
						'email_id' => $next_id,
						'name' => $rows[$i]['name'],
						'email' => $rows[$i]['email'],
					);
					// 放入发送队列
					Resque::enqueue('emailing', 'Sher_Core_Jobs_Emailing', $args);
					
					Doggy_Log_Helper::warn("sending edm end next_id: ".$next_id);
					
					// 记录发送次数
					$send_count += 1;
				}
				
				if($max < $size){
					$is_end = true;
					Doggy_Log_Helper::warn("Waiting edm is end!");
					
					// 设置完成状态
					$model->mark_set_finish($edm_id);
					
					break;
				}
				
				$page += 1;
			}
			
			unset($model);
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Queue edm failed: ".$e->getMessage());
		}
		
	}
	
	/**
	 * After perform
	 * Remov environment for this job
	 */
	public function tearDown(){}
	
}
?>