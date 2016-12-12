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
		$express_caty = $this->stash['express_caty'];
		$express_no = $this->stash['express_no'];
		if (empty($rid) || empty($express_caty) || empty($express_no)) {
			return $this->api_json('缺少请求参数！', 3001);
		}

		$order_model = new Sher_Core_Model_Orders();
		
		$order = $order_model->find_by_rid($rid);

        if(empty($order)){
            return $this->api_json('订单不存在!', 3002);
        }
		
        $ok = $order_model->sended_order((string)$order['_id'], array('express_caty'=>$express_caty, 'express_no'=>$express_no, 'user_id'=>$order['user_id']));

        // 短信提醒用户
        if($ok){
            $order_message = sprintf("亲爱的伙伴：我们已将您编号为（%s）的宝贝托付到有颜靠谱的快递小哥手中，希望Fiu为您带去更新鲜的生活方式和更奇妙的生活体验。", $order_info['rid']);
            $order_phone = $order['express_info']['phone'];
            if(!empty($order_phone)){
                Sher_Core_Helper_Util::send_yp_defined_fiu_mms($order_phone, $order_message);
            }

            return $this->api_json('success', 0, array('rid'=>$rid));
        }else{
            return $this->api_json('订单更新失败！', 3003);
        }

		
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


}

