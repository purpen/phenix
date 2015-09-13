<?php
/**
 * 通知服务
 * @author tianshuai
 */
class Sher_Core_Jobs_Notice extends Doggy_Object {
	
	/**
	 * Before perform
	 * Set up environment for the job
	 */
	public function setUp(){}
	
	/**
	 * Run job
	 */
	public function perform(){
		Doggy_Log_Helper::debug("Make notice task jobs!");
		$notice_id = $this->args['notice_id'];
		
		try{
			if(empty($notice_id)){
				Doggy_Log_Helper::warn("Waiting notice_id is empty!");
				return false;
			}
			// 检测私信列表是否存在
			$model = new Sher_Core_Model_Notice();
			$result = $model->load($notice_id);
			if(empty($result)){
				Doggy_Log_Helper::warn("Waiting notice is empty!");
				return false;
			}
			if($result['state'] != Sher_Core_Model_Notice::STATE_BEGIN){
				Doggy_Log_Helper::warn("Letter isnot waiting!");
				return false;
			}
			
			// 设置为正在发送
			$model->update_set($notice_id, array('state'=>1));
			
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
				if(!empty($next_id)){
					$query = array(
						//'_id' => array('$gt' => $next_id)
					);
				}

        $query['kind'] = array('$ne'=>9);
				$options = array(
					'page' => $page,
					'size' => $size,
					'sort' => array('_id' => 1),
				);
				$rows = $user_model->find($query, $options);
				if(empty($rows)){
					$is_end = true;
					Doggy_Log_Helper::warn("User list is empty!");
					// 设置完成状态
					$model->update_set($notice_id, array('state'=>Sher_Core_Model_Notice::STATE_FINISH, 'send_count'=>$send_count));
					break;
				}
				$max = count($rows);
				for($i=0;$i<$max;$i++){
          $user_id = $next_id = $rows[$i]['_id'];
					// 更新用户提醒数
          $user_model->update_counter_byinc($user_id, 'notice_count', 1);
          //echo "update user $user_id is success...\n";
					// 记录发送次数
					$send_count += 1;
				}
				
				if($max < $size){
					$is_end = true;
					Doggy_Log_Helper::warn("Waiting notice is end!");
					// 设置完成状态
					$model->update_set($notice_id, array('state'=>Sher_Core_Model_Notice::STATE_FINISH, 'send_count'=>$send_count));
					
					break;
				}
				
				$page += 1;
			}
			
			unset($user_model);
			unset($model);
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Queue notice failed: ".$e->getMessage());
      // 设置状态失败
      $model->update_set($notice_id, array('state'=>Sher_Core_Model_Notice::STATE_FAIL));
		}
		
	}
	
	/**
	 * After perform
	 * Remov environment for this job
	 */
	public function tearDown(){}
	
}

