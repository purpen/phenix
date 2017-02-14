<?php
/**
 * WAPI 购物车接口
 * @author tianshuai
 */
class Sher_WApi_Action_Cart extends Sher_WApi_Action_Base {
	
	protected $filter_auth_methods = array('execute', 'fetch_cart_count');

	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}

    /**
     * 我的购物车
     */
    public function fetch_cart(){
		$user_id = $this->uid;

        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        if(empty($cart) || empty($cart['items'])){
            return $this->wapi_json('数据为空!', 0, array('_id'=>0, 'items'=>array(), 'sku_mode'=>null, 'item_count'=>0, 'total_price'=>0));
        }

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();

        $total_price = 0.0;
        $item_arr = array();
        // 记录错误数据索引
        $error_index_arr = array();
        foreach($cart['items'] as $k=>$v){
            // 初始参数
            $target_id = (int)$v['target_id'];
            $type = (int)$v['type'];
            $n = (int)$v['n'];
            $vop_id = isset($v['vop_id']) ? $v['vop_id'] : ''; 
            $referral_code = isset($v['referral_code']) ? $v['referral_code'] : '';
            $storage_id = isset($v['storage_id']) ? (string)$v['storage_id'] : '';

            $data = array();
            $data['target_id'] = $target_id;
            $data['type'] = $type;
            $data['selected'] = true;
            $data['n'] = $n;
            $data['sku_mode'] = null;
            $data['sku_name'] = null;
            $data['price'] = 0;
            $data['vop_id'] = $vop_id;
            $data['referral_code'] = $referral_code;
            $data['storage_id'] = $storage_id;

            if($type==2){
                $inventory = $inventory_model->load($target_id);
                if(empty($inventory)){
                    array_push($error_index_arr, $k);
                    continue;
                }
                $product_id = $inventory['product_id'];
                $data['sku_mode'] = $inventory['mode'];
                $data['sku_name'] = $inventory['mode'];
                $data['price'] = $inventory['price'];
                $data['total_price'] = $data['price']*$n;
        
            }else{
                $product_id = $target_id;
            }

            $data['product_id'] = $product_id;

            $product = $product_model->extend_load($product_id);
            if(empty($product)){
                array_push($error_index_arr, $k);
                continue;     
            }

            $data['title'] = $product['title'];
            $data['cover'] = $product['cover']['thumbnails']['mini']['view_url'];

            if(empty($data['price'])){
                $data['price'] = (float)$product['sale_price'];
                $data['total_price'] = $product['sale_price']*$n;
            }
            $total_price += $data['total_price'];
            array_push($item_arr, $data);

        }//endfor

        // 移除不存在的商品ID
        if(!empty($error_index_arr)){
            foreach($error_index_arr as $k=>$v){
            unset($cart['items'][$v]);
            }
            $cart_model->update_set($cart['_id'], array('items'=>$cart['items'], 'item_count'=>count($cart['items'])));
        }

        $cart['items'] = $item_arr;
        $cart['total_price'] = $total_price;
        return $this->wapi_json('请求成功！', 0, $cart);
    }

    /**
     * 我的购物车数量
     */
    public function fetch_cart_count(){
		$user_id = $this->uid;
        if(empty($user_id)){
            return $this->api_json('success', 0, array('count'=>0)); 
        }
        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        if(empty($cart)){
            $count = 0;
        }else{
            $count = $cart['item_count'];
        }
        return $this->wapi_json('success', 0, array('count'=>$count));
    }

    /**
     * 添加购物车
     */
    public function add_cart(){
		$user_id = $this->uid;

        $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
        $type = 2;
        $n = isset($this->stash['n']) ? (int)$this->stash['n'] : 1;
        // 推广码
        $referral_code = isset($this->stash['referral_code']) ? $this->stash['referral_code'] : '';
        $storage_id = isset($this->stash['storage_id']) ? (string)$this->stash['storage_id'] : '';
        $vop_id = null;

        if(empty($target_id)){
            return $this->wapi_json('缺少请求参数！', 3001); 
        }

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();

        if($type==2){
            $enoughed = $inventory_model->verify_enough_quantity($target_id, $n);
            if(!$enoughed){
                return $this->wapi_json('挑选的产品已售完', 3002);
            }
            $inventory = $inventory_model->load($target_id);
            $product_id = $inventory['product_id'];
            $vop_id = isset($inventory['vop_id']) ? $inventory['vop_id'] : null;
        }elseif($type==1){
            $product_id = $target_id;
        }else{
            return $this->wapi_json('类型不正确!', 3009);
        }

		// 获取产品信息
		$product = $product_model->extend_load($product_id);
		if(empty($product)){
            return $this->wapi_json('挑选的产品不存在或被删除，请核对！', 3003);
        }

        //预售商品不能加入购物车
        if($product['stage'] != 9){
            return $this->wapi_json('类型不是商品，不可加入购物车！', 3004);     
        }

        //是否是抢购商品
        if($product['snatched'] == 1){
            return $this->wapi_json('抢购商品,不能加入购物车！', 3005);
        }

        //试用产品，不可购买
        if($product['is_try']){
            return $this->wapi_json('试用产品，不可购买！', 3006);
        }

        // 验证库存
        if(empty($product['inventory']) || $product['inventory']<$n){
            return $this->wapi_json('库存告及！', 3007);   
        }

        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        $data = array();
        if(empty($cart)){
            $ok = $cart_model->create(array(
                '_id' => (int)$user_id,
                'kind' => 1,
                'state' => 1,
                'remark' => null,
                'items' => array(array('target_id'=>$target_id, 'product_id'=>$product_id, 'type'=>$type, 'n'=>$n, 'vop_id' => $vop_id, 'referral_code'=>$referral_code, 'storage_id'=>$storage_id)),
                'item_count' => 1,
            ));     
        }else{
            $new_item = true;
            foreach($cart['items'] as $k=>$v){
                if($v['target_id']==$target_id){
                    $new_item = false;
                    $cart['items'][$k]['n'] = $v['n']+$n;
                    break;         
                }
            }// endfor

            if($new_item){
                array_push($cart['items'], array('target_id'=>$target_id, 'product_id'=>$product_id, 'type'=>$type, 'n'=>$n, 'vop_id'=>$vop_id, 'referral_code'=>$referral_code, 'storage_id'=>$storage_id));
            }
            $ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items'])));

        } // endif empty cart

        if(!$ok){
            return $this->wapi_json('添加失败！', 3008);
        }
    
        return $this->wapi_json('添加成功!', 0);
    }

    /**
     * 移除购物车(批量)
     */
    public function remove_cart(){
		$user_id = $this->uid;

        if(!isset($this->stash['array']) || empty($this->stash['array'])){
            return $this->wapi_json('缺少请求参数！', 3001); 
        }
        $cart_arr = json_decode($this->stash['array']);

        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        if(empty($cart) || empty($cart['items'])){
            return $this->wapi_json('购物车为空！', 3002);    
        }

        foreach($cart_arr as $key=>$val){
            $val = (array)$val;
            $target_id = (int)$val['target_id'];

            // 批量删除
            foreach($cart['items'] as $k=>$v){
                if($v['target_id']==$target_id){
                    unset($cart['items'][$k]);
                }
            }
        }// endfor

        $ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items'])));  
        $data = $cart_model->find_by_id($user_id);
        return $this->wapi_json('移除成功!', 0, $data);

    }

    /**
     * 编辑购物车--只增减数量
     */
    public function edit_cart(){
 		$user_id = $this->uid;

        if(!isset($this->stash['array']) || empty($this->stash['array'])){
            return $this->wapi_json('请传入参数！', 3002); 
        }
        $cart_arr = json_decode($this->stash['array']);

        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        if(empty($cart) || empty($cart['items'])){
            return $this->wapi_json('购物车为空!', 3002);
        }

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();

        foreach($cart_arr as $key=>$val){
            $val = (array)$val;
            $target_id = (int)$val['target_id'];
            $n = (int)$val['n'];

            // 批量更新数量
            foreach($cart['items'] as $k=>$v){
                if($v['target_id']==$target_id){
                    $cart['items'][$k]['n'] = $n;
                }
            }
        }// endfor
        $ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items']))); 
        if(!$ok){
            return $this->wapi_json('更新失败!', 3003);    
        }
        return $this->wapi_json('success!', 0, array()); 
    }

	

}

