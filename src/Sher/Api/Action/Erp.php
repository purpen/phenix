<?php
/**
 * API Erp 接口
 * @author tianshuai
 */
class Sher_Api_Action_Erp extends Sher_Api_Action_Base {
	
	protected $filter_user_method_list = "*";

	/**
	 * 入口
	 */
	public function execute(){
		return $this->product_list();
	}

	/**
	 * 商品列表
	 */
	public function product_list(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		
		$some_fields = array(
            '_id'=>1, 'title'=>1, 'short_title'=>1, 'advantage'=>1, 'sale_price'=>1, 'market_price'=>1,
            'tags'=>1, 'created_on'=>1, 'updated_on'=>1, 'brand_id'=>1, 'deleted'=>1, 'number'=>1,
			'cover_id'=>1, 'category_id'=>1, 'stage'=>1, 'summary'=>1, 'inventory'=>1, 'category_tags'=>1,
            'is_vop'=>1,
		);
		
		// 请求参数
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$category_tags = isset($this->stash['category_tags']) ? $this->stash['category_tags'] : null;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$brand_id = isset($this->stash['brand_id']) ? $this->stash['brand_id'] : null;
		$stage = isset($this->stash['stage']) ? $this->stash['stage'] : Sher_Core_Model_Product::STAGE_SHOP;
		$title = isset($this->stash['title']) ? $this->stash['title'] : null;
			
		$query   = array();
		$options = array();

        $query['stage'] = 9;
		
		// 查询条件
		if($category_id) $query['category_id'] = (int)$category_id;

        // 查询条件
        if($category_tags){
          $category_tag_arr = explode(',', $category_tags);
          $query['category_tags'] = array('$in'=>$category_tag_arr);
        }

        // 品牌
        if($brand_id) $query['brand_id'] = $brand_id;

		if($user_id) $query['user_id'] = (int)$user_id;

		// 已发布上线
		$query['published'] = 1;
		
		if($stick) $query['stick'] = $stick;

        $query['deleted'] = 0;

        // 模糊查标签
        if(!empty($title)) $query['title'] = array('$regex'=>$title);
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'vote';
				break;
			case 2:
				$options['sort_field'] = 'love';
				break;
			case 3:
				$options['sort_field'] = 'comment';
				break;
			case 4:
				$options['sort_field'] = 'stick:update';
				break;
			case 5:
				$options['sort_field'] = 'featured:update';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		// 开启查询
    $product_model = new Sher_Core_Model_Product();
    $service = Sher_Core_Service_Product::instance();
    $result = $service->get_product_list($query, $options);
		
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:0;
			}
            if($data[$i]['category_tags']==0){
                $data[$i]['category_tags'] = array();
            }
			// 封面图url
			$data[$i]['cover_url'] = $result['rows'][$i]['cover']['fileurl'];
			// 用户信息

          // 保留2位小数
          $data[$i]['sale_price'] = sprintf('%.2f', $result['rows'][$i]['sale_price']);

		} // endfor
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * sku列表
	 */
	public function sku_list(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		
		$some_fields = array(
            '_id'=>1, 'mode'=>1, 'product_id'=>1, 'number'=>1, 'name'=>1, 'price'=>1,
            'quantity'=>1, 'created_on'=>1, 'updated_on'=>1, 'summary'=>1, 'stage'=>1, 'status'=>1,
            'vop_id'=>1,
		);
		
		// 请求参数
		$stage = isset($this->stash['stage']) ? (int)$this->stash['stage'] : 0;
		$number = isset($this->stash['number']) ? (int)$this->stash['number'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$mode = isset($this->stash['mode']) ? $this->stash['mode'] : null;
			
		$query   = array();
		$options = array();

        $query['stage'] = 9;
		
		// 查询条件
		if($number) $query['number'] = $number;

        // 模糊查标签
        if(!empty($mode)) $query['mode'] = array('$regex'=>$mode);
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		// 开启查询
        $inventory_model = new Sher_Core_Model_Inventory();
        $service = Sher_Core_Service_Inventory::instance();
        $result = $service->get_sku_list($query, $options);
		
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
            $data[$i]['product_number'] = 0;
            if(!empty($result['rows'][$i]['product'])){
                $data[$i]['product_number'] = $result['rows'][$i]['product']['number'];
            }
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key])?$result['rows'][$i][$key]:0;
			}

          // 保留2位小数
          $data[$i]['price'] = sprintf('%.2f', $result['rows'][$i]['price']);

		} // endfor
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}


	/**
	 * 订单列表
	 * 待支付、待发货、已完成
	 */
	public function order_list(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
        $user_id = isset($this->stash['user_id'])?(int)$this->stash['user_id']:0;
        $status = isset($this->stash['status'])?(int)$this->stash['status']:0;
		
		$query   = array();
		$options = array();
		
		// 查询条件
        if($user_id) $query['user_id'] = (int)$user_id;
		
		switch($status){
			case 1: // 待支付订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;
				break;
			case 2: // 待发货订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_READY_GOODS;
				break;
			case 3: // 待收货订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_SENDED_GOODS;
				break;
			case 4: // 待评价订单
				$query['status'] = Sher_Core_Util_Constant::ORDER_EVALUATE;
				break;
			case 8: // 退换货
				$query['status'] = array(
					'$in' => array(Sher_Core_Util_Constant::ORDER_READY_REFUND, Sher_Core_Util_Constant::ORDER_REFUND_DONE),
				);
				break;
		}

        $query['deleted'] = 0;

        //限制输出字段
		$some_fields = array(
			'_id'=>1, 'rid'=>1, 'items'=>1, 'items_count'=>1, 'total_money'=>1, 'pay_money'=>1, 'referral_code'=>1, 'referral'=>1,
			'card_money'=>1, 'coin_money'=>1, 'freight'=>1, 'discount'=>1, 'user_id'=>1, 'discount_money'=>1,
			'express_info'=>1, 'invoice_type'=>1, 'invoice_caty'=>1, 'invoice_title'=>1, 'invoice_content'=>1,
			'payment_method'=>1, 'express_caty'=>1, 'express_no'=>1, 'sended_date'=>1,'card_code'=>1, 'is_presaled'=>1,
            'expired_time'=>1, 'from_site'=>1, 'status'=>1, 'gift_code'=>1, 'bird_coin_count'=>1, 'bird_coin_money'=>1,
            'gift_money'=>1, 'status_label'=>1, 'created_on'=>1, 'updated_on',
            // 子订单
            'exist_sub_order'=>1, 'sub_orders'=>1,
            // 是否京东订单
            'jd_order_id'=>1,
		);
		$options['some_fields'] = $some_fields;

		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'latest';
		
		// 开启查询
        $service = Sher_Core_Service_Orders::instance();
        $result = $service->get_latest_list($query, $options);


        $product_model = new Sher_Core_Model_Product();
        $sku_model = new Sher_Core_Model_Inventory();
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : null;
			}
			// ID转换为字符串
			$data[$i]['_id'] = (string)$result['rows'][$i]['_id'];
      // 创建时间格式化 
      $data[$i]['created_at'] = date('Y-m-d H:i', $result['rows'][$i]['created_on']); 
      //收货地址
      if(empty($data[$i]['express_info'])){
        $data[$i]['express_info'] = null;
      }

      //商品详情
      if(!empty($result['rows'][$i]['items'])){
        $m = 0;
        foreach($result['rows'][$i]['items'] as $k=>$v){
          $d = $product_model->extend_load((int)$v['product_id']);
          if(!empty($d)){
            $sku_mode = null;
            if($v['sku']==$v['product_id']){
              $data[$i]['items'][$m]['name'] = $d['title'];   
            }else{
              $sku_mode = '';
              $sku = $sku_model->find_by_id($v['sku']);
              if(!empty($sku)){
                $sku_mode = $sku['mode'];
              }
              $data[$i]['items'][$m]['name'] = $d['title']; 
            }
            $data[$i]['items'][$m]['sku_name'] = $sku_mode; 
            $data[$i]['items'][$m]['cover_url'] = $d['cover']['thumbnails']['apc']['view_url'];
          }

          $m++;
        }
      }
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}

	/**
	 * 确认发货
	 */
	public function send_goods(){
		$rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
		$express_caty = isset($this->stash['express_caty']) ? $this->stash['express_caty'] : null;
		$express_no = isset($this->stash['express_no']) ? $this->stash['express_no'] : null;

        $all_sended = true;

        // 子订单发货状态
        $array = isset($this->stash['array']) ? $this->stash['array'] : null;

		if (empty($rid)) {
			return $this->api_json('订单号不存在！', 3001);
		}

		$model = new Sher_Core_Model_Orders();
		
		$order = $model->find_by_rid($rid);

        if(empty($order)){
            return $this->api_json('订单不存在!', 3002);
        }

        // 仅待发货订单
        if ($order['status'] != Sher_Core_Util_Constant::ORDER_READY_GOODS) {
            return $this->api_json('订单非待发货状态！', 3003);
        }

        // 是否否有子订单
        $is_sub_order = !empty($order['exist_sub_order']) ? true : false;

        if($is_sub_order){

            if(empty($array)){
                return $this->api_json('缺少子订单请求参数!', 3004);
            }

            $array = Sher_Core_Helper_Util::object_to_array(json_decode($array)); 
            if(count($array)<=1){
                return $this->api_json('至少两个订单!', 3005);
            }

            for($i=0;$i<count($array);$i++){
                $sub_order_id = isset($array[$i]['id']) ? $array[$i]['id'] : null;
                $express_caty = isset($array[$i]['express_caty']) ? $array[$i]['express_caty'] : null;
                $express_no = isset($array[$i]['express_no']) ? $array[$i]['express_no'] : null;
                if(empty($sub_order_id) || empty($express_caty) || empty($express_no)){
                    return $this->api_json('子订单物流信息不完整!', 3009);               
                }
                
                for($j=0;$j<count($order['sub_orders']);$j++){
                    if($order['sub_orders'][$j]['id']==$sub_order_id){
                        $order['sub_orders'][$j]['is_sended'] = 1;
                        $order['sub_orders'][$j]['express_caty'] = $express_caty;
                        $order['sub_orders'][$j]['express_no'] = $express_no;
                        $order['sub_orders'][$j]['sended_on'] = time();
                    }
                
                }   // endfor

            }   // endfor

            if(!$all_sended){
                return $this->api_json('更新子订单失败，子订单数量不全！', 3006);               
            }
            $sub_ok = $model->update_set((string)$order['_id'], array('sub_orders'=>$order['sub_orders']));
            if(!$sub_ok){
                return $this->api_json('更新子订单物流失败！', 3007);                   
            }
        }   // endif is_sub_order

        if (empty($express_caty) || empty($express_no)) {
            return $this->api_json('缺少请求参数！', 3008);
        }

        $ok = $model->sended_order((string)$order['_id'], array('express_caty'=>$express_caty, 'express_no'=>$express_no, 'user_id'=>$order['user_id']));

        if(!$ok){
            return $this->api_json('更新订单失败！', 3010);
        }

        // 短信提醒用户
        $order_message = sprintf("亲爱的伙伴：我们已将您编号为（%s）的宝贝托付到有颜靠谱的快递小哥手中，希望Fiu为您带去更新鲜的生活方式和更奇妙的生活体验。", $order['rid']);
        $order_phone = $order['express_info']['phone'];
        if(!empty($order_phone)){
            Sher_Core_Helper_Util::send_yp_defined_fiu_mms($order_phone, $order_message);
        }

        return $this->api_json('success', 0, array('rid'=>$rid));
		
	}

    /**
     * 拆单
     */
    public function split_order(){

        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
        $array = isset($this->stash['array']) ? $this->stash['array'] : null;
        if(empty($rid) || empty($array)){
            return $this->api_json('缺少请求参数!', 3001);
        }
		$model = new Sher_Core_Model_Orders();
		$order = $model->find_by_rid($rid);
        if(empty($order)){
            return $this->api_json('订单不存在!', 3002);
        }

        // 可拆单的状态
        $arr = array(
            Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT,
            Sher_Core_Util_Constant::ORDER_READY_GOODS, 
        );
        if(!in_array($order['status'], $arr)){
            return $this->api_json('该订单状态不允许拆分操作!', 3003);
        }

        if(!empty($order['exist_sub_order'])){
            return $this->api_json('不允许重复操作!', 3008);       
        }

        $array = Sher_Core_Helper_Util::object_to_array(json_decode($array)); 
        if(count($array)<=1){
            return $this->api_json('至少拆分两个订单!', 3004);
        }
    
        $sub_orders = array();
        for($i=0;$i<count($array);$i++){
            $sub_order = array();
            $sub_order_id = $array[$i]['id'];

            if(!is_array($array[$i]['items'])){
                return $this->api_json('子订单商品列表非数组结构!', 3005);
            }

            $items = array();
            for($j=0;$j<count($array[$i]['items']);$j++){
                $sku_number = $array[$i]['items'][$j];
                if(empty($sku_number)){
                    return $this->api_json('参数结构不正确02!', 3006);
                }

                for($k=0;$k<count($order['items']);$k++){
                    if($order['items'][$k]['number']==$sku_number){
                        $item = $order['items'][$k];
                        array_push($items, $item);
                        break;
                    }
                }
            }   // endfor

            if(empty($items)){
                return $this->api_json('子订单商品信息不存在!', 3007);  
            }
            $sub_order['id'] = $sub_order_id;
            $sub_order['items'] = $items;
            $sub_order['items_count'] = count($items);
            $sub_order['split_on'] = time();
            $sub_order['is_sended'] = 0;
            $sub_order['sended_on'] = 0;
            $sub_order['express_caty'] = '';
            $sub_order['express_no'] = '';
            $sub_order['supplier_id'] = '';

            array_push($sub_orders, $sub_order);
        }   // endfor

        if(empty($sub_orders)){
            return $this->api_json('无法获取子订单!', 3008);       
        }

        $ok = $model->update_set((string)$order['_id'], array('exist_sub_order'=>1 ,'sub_orders'=>$sub_orders));

        if(!$ok){
            return $this->api_json('拆单保存失败!', 3009);            
        }

        return $this->api_json('success', 0, array('rid'=>$rid));
    }

    /**
     * 更新sku库存
     */
    public function update_inventory(){
        $number = isset($this->stash['number']) ? $this->stash['number'] : null;
        $quantity = isset($this->stash['quantity']) ? (int)$this->stash['quantity'] : 0;
        if(empty($number)){
            return $this->api_json('缺少请求参数！', 3001);           
        }

        $inventory_model = new Sher_Core_Model_Inventory();
        $inventory = $inventory_model->find_number_id($number);
        if(empty($inventory)){
            return $this->api_json('内容不存在！', 3002);            
        }

        $old_inventory = $inventory['quantity'];
        // 增量
        $increment = $quantity - $old_inventory;
        $ok = $inventory_model->update_set($inventory['_id'], array('quantity'=>$quantity));
        if(!$ok){
            return $this->api_json('更新库存失败！', 3003);        
        }

        // 更新商品库存
        $product_id = (int)$inventory['product_id'];
        $product_model = new Sher_Core_Model_Product();
        $product = $product_model->load($product_id);
        if($product){
            $new_inventory = $product['inventory'] + $increment;
            if($new_inventory < 0) $new_inventory = 0;
            $product_model->update_set($product['_id'], array('inventory'=>$new_inventory));
        }

        return $this->api_json('success', 0, array('number'=>$number));
    }

	/**
	 * 退款单列表
	 */
	public function refund_list(){

		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		$type = isset($this->stash['type'])?(int)$this->stash['type']:0;
		$stage = isset($this->stash['stage'])?(int)$this->stash['stage']:0;

		$query   = array();
		$options = array();

        if($type){
            $query['type'] = $type;
        }
        if($stage){
            $query['stage'] = $stage;
        }
        $query['deleted'] = 0;

        //限制输出字段
		$some_fields = array(
			'_id'=>1, 'number'=>1, 'user_id'=>1, 'target_id'=>1, 'product_id'=>1, 'target_type'=>1, 'stage_label'=>1,
			'order_rid'=>1, 'sub_order_id'=>1, 'refund_price'=>1, 'quantity'=>1, 'type'=>1, 'type_label'=>1, 'freight'=>1,
			'stage'=>1, 'reason'=>1, 'reason_label'=>1, 'content'=>1, 'summary'=>1, 'status'=>1, 'deleted'=>1,
            'created_on'=>1, 'updated_on'=>1, 'reason_label'=>1, 'refund_on'=>1, 'batch_no'=>1, 'sku_number'=>1,
		);
		$options['some_fields'] = $some_fields;

		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'latest';
		
		// 开启查询
        $service = Sher_Core_Service_Refund::instance();
        $result = $service->get_refund_list($query, $options);

        $product_model = new Sher_Core_Model_Product();
        $sku_model = new Sher_Core_Model_Inventory();

		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			foreach($some_fields as $key=>$value){
				$data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : null;
			}

            $item = array();
            $product = $product_model->extend_load($data[$i]['product_id']);
            $item['title'] = $product['title']; 
            $item['name'] = $product['title']; 
            $item['short_title'] = $product['short_title'];
            $item['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
            $item['sale_price'] = $product['sale_price'];
            $item['quantity'] = $data[$i]['quantity'];

            $item['sku_name'] = '默认';
            if($data[$i]['target_type']==1){
                $sku = $sku_model->find_by_id($data[$i]['target_id']);
                if($sku){
                    $item['sku_name'] = $sku['mode']; 
                }
            }

            $data[$i]['product'] = $item;

            $data[$i]['refund_at'] = '';
            if(!empty($data[$i]['refund_on'])){
                $data[$i]['refund_at'] = date('y-m-d', $data[$i]['refund_on']);
            }
            $data[$i]['created_at'] = date('y-m-d', $data[$i]['created_on']);

        }   // endfor

		$result['rows'] = $data;
		return $this->api_json('请求成功', 0, $result);
	}

    /**
     * 退款单详情
     */
    public function refund_show(){
        $number = isset($this->stash['number']) ? $this->stash['number'] : 0;
        if(empty($number)){
            return $this->api_json('缺少请求参数！', 3001);
        }

        // 退款单Model
        $refund_model = new Sher_Core_Model_Refund();
        $refund = $refund_model->find_by_number($number);

        if(empty($refund)){
            return $this->api_json('数据不存在！', 3002);
        }

        $product_model = new Sher_Core_Model_Product();
        $sku_model = new Sher_Core_Model_Inventory();

        $item = array();
        $product = $product_model->extend_load($refund['product_id']);
        $item['title'] = $product['title']; 
        $item['name'] = $product['title']; 
        $item['short_title'] = $product['short_title'];
        $item['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
        $item['sale_price'] = $product['sale_price'];
        $item['quantity'] = $refund['quantity'];

        $item['sku_name'] = '默认';
        if($refund['product_id'] != $refund['target_id']){
            $sku = $sku_model->find_by_id($refund['target_id']);
            if($sku){
                $item['sku_name'] = $sku['mode'];
                $item['sale_price'] = $sku['price'];
            }
        }

        $refund['refund_at'] = '';
        if(!empty($refund['refund_on'])){
            $refund['refund_at'] = date('y-m-d H:i', $refund['refund_on']);           
        }
        $refund['created_at'] = date('Y-m-d H:i', $refund['created_on']);

        $refund['product'] = $item;
        return $this->api_json('success', 0, $refund); 
    
    }

    /**
     * 订单详情
     */
    public function order_show(){
 		$rid = $this->stash['rid'];
		if(empty($rid)){
			return $this->api_json('订单ID不存在！', 3000);
		}
        //限制输出字段
		$some_fields = array(
			'_id', 'rid', 'items', 'items_count', 'total_money', 'pay_money', 'discount_money',
			'card_money', 'coin_money', 'freight', 'discount', 'user_id', 'addbook_id', 'addbook',
			'express_info', 'invoice_type', 'invoice_caty', 'invoice_title', 'invoice_content', 'trade_site_name',
			'payment_method', 'express_caty', 'express_company', 'express_no', 'sended_date','card_code', 'is_presaled',
            'expired_time', 'from_site', 'status', 'gift_code', 'bird_coin_count', 'bird_coin_money',
            'gift_money', 'status_label', 'created_on', 'updated_on',
            // 子订单
            'exist_sub_order', 'sub_orders'
		);
		
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);

        $product_model = new Sher_Core_Model_Product();
        $sku_model = new Sher_Core_Model_Inventory();

        // 重建数据结果
        $data = array();
        for($i=0;$i<count($some_fields);$i++){
          $key = $some_fields[$i];
          $data[$key] = isset($order_info[$key]) ? $order_info[$key] : null;
        }

        $data['_id'] = (string)$data['_id'];
        // 创建时间格式化 
        $data['created_at'] = date('Y-m-d H:i', $data['created_on']);

        // 收货信息
        if(empty($data['express_info'])){
          $data['express_info'] = null;
          if(isset($order_info['addbook'])){
            $data['express_info']['name'] = $order_info['addbook']['name'];
            $data['express_info']['phone'] = $order_info['addbook']['phone'];
            $data['express_info']['zip'] = $order_info['addbook']['zip'];
            $data['express_info']['province'] = $order_info['addbook']['area_province']['city'];
            $data['express_info']['city'] = $order_info['addbook']['area_district']['city'];
          }   
        }

        // 快递公司
        $data['express_company'] = null;
        if(!empty($data['express_caty'])){
          $express_company_arr = $model->find_express_category($data['express_caty']);
          $data['express_company'] = $express_company_arr['title'];
        }

        //商品详情
        if(!empty($data['items'])){
          $m = 0;
          foreach($data['items'] as $k=>$v){

              $item = $data['items'][$m];
                $data['items'][$m]['refund_label'] = '';
                if(isset($item['refund_type']) && $item['refund_type'] != 0){
                    switch((int)$item['refund_status']){
                        case 0:
                            $data['items'][$m]['refund_label'] = '商家拒绝退款';
                            break;
                        case 1:
                            $data['items'][$m]['refund_label'] = '退款中';
                            break;
                        case 2:
                            $data['items'][$m]['refund_label'] = '已退款';
                            break;
                    }   
                }
                // 退货款按钮状态
                $data['items'][$m]['refund_button'] = 0;
                if(!isset($item['refund_type']) || $item['refund_type'] == 0){
                    if(in_array($data['status'], array(Sher_Core_Util_Constant::ORDER_READY_GOODS))){ // 退款状态
                        $data['items'][$m]['refund_button'] = 1;           
                    }elseif(in_array($data['status'], array(Sher_Core_Util_Constant::ORDER_SENDED_GOODS,Sher_Core_Util_Constant::ORDER_EVALUATE))){   // 退货状态
                        $data['items'][$m]['refund_button'] = 2;
                    }
                }

            $d = $product_model->extend_load((int)$v['product_id']);
            if(!empty($d)){
              $sku_mode = '默认';
              if($v['sku']==$v['product_id']){
                $data['items'][$m]['name'] = $d['title'];   
              }else{
                $sku = $sku_model->find_by_id($v['sku']);
                if(!empty($sku)){
                  $sku_mode = $sku['mode'];
                }
                $data['items'][$m]['name'] = $d['title']; 
              }
              $data['items'][$m]['sku_name'] = $sku_mode;
              $data['items'][$m]['cover_url'] = $d['cover']['thumbnails']['apc']['view_url'];

              // 退货款信息
              $data['items'][$m]['refund_type'] = isset($data['items'][$m]['refund_type']) ? (int)$data['items'][$m]['refund_type'] : 0;
              $data['items'][$m]['refund_status'] = isset($data['items'][$m]['refund_status']) ? (int)$data['items'][$m]['refund_status'] : 0;
            }

            $m++;
          } // endforeach
        }

        // 子订单详情
        if(isset($data['exist_sub_order']) && !empty($data['exist_sub_order'])){
            for($i=0;$i<count($data['sub_orders']);$i++){
                $sub_order = $data['sub_orders'][$i];
                $m = 0;
                foreach($data['sub_orders'][$i]['items'] as $k=>$v){
                    $item = $data['sub_orders'][$i]['items'][$m];

                    $data['sub_orders'][$i]['items'][$m]['refund_label'] = '';
                    if(isset($item['refund_type']) && $item['refund_type'] != 0){
                        switch((int)$item['refund_status']){
                            case 0:
                                $data['sub_orders'][$i]['items'][$m]['refund_label'] = '商家拒绝退款';
                                break;
                            case 1:
                                $data['sub_orders'][$i]['items'][$m]['refund_label'] = '退款中';
                                break;
                            case 2:
                                $data['sub_orders'][$i]['items'][$m]['refund_label'] = '已退款';
                                break;
                        }   
                    }
                    // 退货款按钮状态
                    $data['sub_orders'][$i]['items'][$m]['refund_button'] = 0;
                    if(!isset($item['refund_type']) || $item['refund_type'] == 0){
                        if(in_array($data['status'], array(Sher_Core_Util_Constant::ORDER_READY_GOODS))){ // 退款状态
                            $data['sub_orders'][$i]['items'][$m]['refund_button'] = 1;           
                        }elseif(in_array($data['status'], array(Sher_Core_Util_Constant::ORDER_SENDED_GOODS,Sher_Core_Util_Constant::ORDER_EVALUATE))){   // 退货状态
                            $data['sub_orders'][$i]['items'][$m]['refund_button'] = 2;
                        }
                    }

                    $d = $product_model->extend_load((int)$v['product_id']);
                    if(!empty($d)){
                      $sku_mode = '默认';
                      if($v['sku']==$v['product_id']){
                        $data['sub_orders'][$i]['items'][$m]['name'] = $d['title'];   
                      }else{
                        $sku = $sku_model->find_by_id($v['sku']);
                        if(!empty($sku)){
                          $sku_mode = $sku['mode'];
                        }
                        $data['sub_orders'][$i]['items'][$m]['name'] = $d['title']; 
                      }
                      $data['sub_orders'][$i]['items'][$m]['sku_name'] = $sku_mode;
                      $data['sub_orders'][$i]['items'][$m]['cover_url'] = $d['cover']['thumbnails']['apc']['view_url'];

                      // 退货款信息
                      $data['sub_orders'][$i]['items'][$m]['refund_type'] = isset($item['refund_type']) ? (int)$item['refund_type'] : 0;
                      $data['sub_orders'][$i]['items'][$m]['refund_status'] = isset($item['refund_status']) ? (int)$item['refund_status'] : 0;
                    }
                    $m++;
                } // endforeach
                
                $data['sub_orders'][$i]['split_at'] = isset($sub_order['split_on']) ? date('Y-m-d H:i', $sub_order['split_on']) : '';
                $data['sub_orders'][$i]['sended_at'] = isset($sub_order['sended_on']) ? date('Y-m-d H:i', $sub_order['sended_on']) : '';
                $data['sub_orders'][$i]['express_company'] = '';
                if(!empty($sub_order['is_sended'])){
                    $express_company_arr = $model->find_express_category($sub_order['express_caty']);
                    $data['sub_orders'][$i]['express_company'] = $express_company_arr['title'];               
                }
            }   // endfor
       
        }
		
		return $this->api_json('请求成功', 0, $data);
    
    }


}

