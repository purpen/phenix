<?php
/**
 * 结算接口
 * @author tianshuai
 */
class Sher_Api_Action_BalanceRecord extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = array('execute');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	

	/**
	 * 列表
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
        $sort = isset($this->stash['sort'])?(int)$this->stash['sort']:0;
        $alliance_id = isset($this->stash['alliance_id']) ? $this->stash['alliance_id'] : null;
        $status = isset($this->stash['status']) ? (int)$this->stash['status'] : 0;

        $user_id = $this->current_user_id;
		
		$query   = array();
		$options = array();

        //显示的字段
        $options['some_fields'] = array(
            '_id'=> 1, 'alliance_id'=>1, 'user_id'=>1, 'balance_count'=>1, 'amount'=>1, 'status'=>1,
            'created_on'=>1, 'updated_on'=>1,
        );
		
		// 查询条件
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($alliance_id){
			$query['alliance_id'] = $alliance_id;
        }

        if($status){
            if($status != -1){
                $query['status'] = 0;
            }else{
                $query['status'] = 1;
            }       
        }

		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

            // 开启查询
        $service = Sher_Core_Service_BalanceRecord::instance();
        $result = $service->get_balance_record_list($query, $options);

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$value){
                $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : 0;
		    }
            $data[$i]['_id'] = (string)$data[$i]['_id'];
            // 创建时间格式化 
            $data[$i]['created_at'] = date('Y-m-d H:i', $data[$i]['created_on']);

		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}


	/**
	 * 结算明细列表
	 */
	public function item_list(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
        $sort = isset($this->stash['sort'])?(int)$this->stash['sort']:0;
        $balance_record_id = isset($this->stash['balance_record_id']) ? $this->stash['balance_record_id'] : null;

        $user_id = $this->current_user_id;
		
		$query   = array();
		$options = array();

        //显示的字段
        $options['some_fields'] = array(
            '_id'=> 1, 'alliance_id'=>1, 'user_id'=>1, 'balance_id'=>1, 'balance_record_id'=>1, 'amount'=>1, 'status'=>1,
            'created_on'=>1, 'updated_on'=>1, 'balance_id'=>1,
        );
		
		// 查询条件
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($balance_record_id){
			$query['balance_record_id'] = $balance_record_id;
        }

		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

        $balance_model = new Sher_Core_Model_Balance();
		$product_model = new Sher_Core_Model_Product();

            // 开启查询
        $service = Sher_Core_Service_BalanceItem::instance();
        $result = $service->get_balance_item_list($query, $options);

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($options['some_fields'] as $key=>$value){
                $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : 0;
		    }
            $data[$i]['_id'] = (string)$data[$i]['_id'];

            $balance = array();
            $row = $balance_model->load($data[$i]['balance_id']);
            if(!empty($row)){
                $product_id = $row['product_id'];
                $p = $product_model->load($product_id);
                if($p){
                    $product = array();
                    $product['title'] = $p['title'];
                    $product['short_title'] = !empty($p['short_title']) ? $p['short_title'] : $p['title'];
                }else{
                    $product = null;
                }
                $balance['_id'] = (string)$row['_id'];
                $balance['product'] = $product;
            }

            $data[$i]['balance'] = $balance;
            // 创建时间格式化 
            $data[$i]['created_at'] = date('Y-m-d H:i', $data[$i]['created_on']);

		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

	
}

