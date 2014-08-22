<?php
/**
 * 系统临时数据清理及定时任务
 * @author purpen
 */
class Sher_Core_Jobs_Clean extends Doggy_Object {
	
	/**
	 * Before perform
	 * Set up environment for the job
	 */
	public function setUp(){}
	
	/**
	 * Run job
	 */
	public function perform(){
		Doggy_Log_Helper::debug("Make clean jobs!");
		
		// 清理临时订单
		$this->temp_order();
		// 自动关闭过期订单
		$this->close_order();
	}
	
	/**
	 * 自动关闭过期订单
	 * 每3分钟检查一次
	 */
	public function close_order(){
		$page = 1;
		$size = 100;
		
		try{
			$model = new Sher_Core_Model_Orders();
			// 过期时间小于当前时间,并且
			$query = array(
				'status' => Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT,
				'expired_time' => array('$lt' => time()),
			);
			$options = array(
				'page' => $page,
				'size' => $size,
				'sort' => array('created_on' => -1),
			);
			$result = $model->find($query, $options);
			if (empty($result)){
				Doggy_Log_Helper::warn("Close order is empty!");
				return false;
			}
			
			$max = count($result);
			for($i=0;$i<$max;$i++){
				// 未支付订单才允许关闭
				if ($result[$i]['status'] == Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
					$expired_model = new Sher_Core_Model_Orders();
					$expired_model->close_order($result[$i]['_id']);
					unset($expired_model);
				} else {
					Doggy_Log_Helper::warn("Close order status: ".$result[$i]['status']);
				}
			}
			
			unset($model);
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Clean temp order failed: ".$e->getMessage());
		}
	}
	
	/**
	 * 清理临时订单数据
	 * 每5分钟检查一次
	 */
	public function temp_order(){
		try{
			$model = new Sher_Core_Model_OrderTemp();
			// 过期时间小于当前时间
			$query = array(
				'expired' => array('$lt' => time()),
			);
			
			$ok = $model->remove($query);
			
			unset($model);
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Clean temp order failed: ".$e->getMessage());
		}
	}
	
	/**
	 * After perform
	 * Remov environment for this job
	 */
	public function tearDown(){}
	
}
?>