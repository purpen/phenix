<?php
/**
 * 佣金接口
 * @author tianshuai
 */
class Sher_Api_Action_Balance extends Sher_Api_Action_Base {
	
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
		$size = isset($this->stash['size'])?(int)$this->stash['size']:6;
        $sort = isset($this->stash['sort'])?(int)$this->stash['sort']:0;
        $order_rid = isset($this->stash['order_rid']) ? $this->stash['order_rid'] : null;
        $product_id = isset($this->stash['product_id']) ? (int)$this->stash['product_id'] : 0;
        $sku_id = isset($this->stash['sku_id']) ? (int)$this->stash['sku_id'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 0;

        $status = isset($this->stash['status']) ? (int)$this->stash['status'] : -1;
        $stage = isset($this->stash['stage']) ? (int)$this->stash['stage'] : 0;

        $user_id = $this->current_user_id;
		
		$query   = array();
		$options = array();

        //显示的字段
        $options['some_fields'] = array(
            '_id'=> 1, 'alliance_id'=>1, 'order_rid'=>1, 'sub_order_id'=>1, 'product_id'=>1, 'sku_id'=>1, 'user_id'=>1,
            'quantity'=>1, 'commision_percent'=>1, 'unit_price'=>1, 'total_price'=>1, 'code'=>1,
            'summary'=>1, 'type'=>1, 'kind'=>1, 'stage'=>1, 'status'=>1, 'status_label'=>1,
            'balance_on'=>1, 'from_site'=>1, 'created_on'=>1, 'updated_on'=>1,
        );
		
		// 查询条件
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($order_rid){
			$query['order_rid'] = $order_rid;
        }

        // 进度
        if($stage){
            $query['state'] = $state;
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
        $service = Sher_Core_Service_Balance::instance();
        $result = $service->get_balance_list($query, $options);

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
	 * 详情
	 */
	public function view(){
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $user_id = $this->current_user_id;
		if(empty($user_id)){
			return $this->api_json('请先登录！', 3000);
        }

        if(empty($id)){
 		    return $this->api_json('缺少请求参数！', 3001);       
        }
		
		$balance_model = new Sher_Core_Model_Balance();
		$balance = $balance_model->load($id);
		
		if(empty($balance)){
			return $this->api_json('内容不存在！', 3002);
		}

        if($balance['user_id'] != $user_id){
 			return $this->api_json('没有权限！', 3003);       
        }

		$product_model = new Sher_Core_Model_Product();

        //显示的字段
        $some_fields = array(
            '_id'=> 1, 'alliance_id'=>1, 'order_rid'=>1, 'sub_order_id'=>1, 'product_id'=>1, 'sku_id'=>1, 'sku_price'=>1, 'user_id'=>1,
            'quantity'=>1, 'commision_percent'=>1, 'unit_price'=>1, 'total_price'=>1, 'code'=>1,
            'summary'=>1, 'type'=>1, 'kind'=>1, 'stage'=>1, 'status'=>1, 'status_label'=>1,
            'balance_on'=>1, 'from_site'=>1, 'created_on'=>1, 'updated_on'=>1,
        );

        // 重建数据结果
        $data = array();
        foreach($some_fields as $key=>$value){
            $data[$key] = isset($balance[$key]) ? $balance[$key] : null;
        }

        $product = $product_model->load($data['product_id']);
        if($product){
            $data['product']['title'] = $product['title'];
            $data['product']['short_title'] = !empty($product['short_title']) ? $product['short_title'] : $product['title'];
        }

        // 创建时间格式化 
        $data['created_at'] = date('Y-m-d H:i', $data['created_on']);

        $data['_id'] = (string)$data['_id'];

		return $this->api_json('请求成功', 0, $data);
	}

	
}

