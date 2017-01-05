<?php
/**
 * 购物车操作-存数据库
 * @author tianshuai
 */
class Sher_App_Action_Cart extends Sher_App_Action_Base {
	
	public $stash = array(

	);

	
	protected $exclude_method_list = array('execute', 'ajax_fetch_count');
	
	/**
	 * 活动
	 */
	public function execute(){

	}
	
	/**
	 * 添加购物车
	 */
	public function ajax_add_cart(){
		$user_id = $this->visitor->id;
    if(empty($user_id)){
      return $this->ajax_json('请先登录！', true); 
    }

    $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
    $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
    $n = isset($this->stash['n']) ? (int)$this->stash['n'] : 1;
    // 推广码
    $referral_code = isset($_COOKIE['referral_code']) ? $_COOKIE['referral_code'] : null;
    $storage_id = isset($this->stash['storage_id']) ? $this->stash['storage_id'] : null;
    $vop_id = null;

    if(empty($target_id) && empty($type)){
      return $this->ajax_json('请选择商品或类型！', true); 
    }

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();

    if($type==2){
      $enoughed = $inventory_model->verify_enough_quantity($target_id, $n);
      if(!$enoughed){
        return $this->ajax_json('挑选的产品已售完', true);
      }
      $inventory = $inventory_model->load($target_id);
      $product_id = $inventory['product_id'];
      $vop_id = isset($inventory['vop_id']) ? $inventory['vop_id'] : null;
    }elseif($type==1){
      $product_id = $target_id;
    }else{
      return $this->ajax_json('类型不正确!', true);
    }

		// 获取产品信息
		$product = $product_model->extend_load($product_id);
		if(empty($product)){
      return $this->ajax_json('挑选的产品不存在或被删除，请核对！', true);
    }

    //预售商品不能加入购物车
    if($product['stage'] != 9){
      return $this->ajax_json('类型不是商品，不可加入购物车！', true);     
    }

    //是否是抢购商品
    if($product['snatched'] == 1){
      return $this->ajax_json('抢购商品,不能加入购物车！', true);
    }

    //试用产品，不可购买
    if($product['is_try']){
      return $this->ajax_json('试用产品，不可购买！', true);
    }

    // 验证库存
    if(empty($product['inventory']) || $product['inventory']<$n){
      return $this->ajax_json('库存告及！', true);   
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
        'items' => array(array('target_id'=>$target_id, 'product_id'=>$product_id, 'type'=>$type, 'n'=>$n, 'vop_id'=>$vop_id, 'referral_code'=>$referral_code, 'storage_id'=>$storage_id)),
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
      return $this->ajax_json('添加失败！', true);
    }
    
    $data = $cart_model->get_data();
    return $this->ajax_json('添加成功!', false, 0, $data);
	}


  /**
   * 删除购物车
   */
  public function ajax_remove_cart(){
		$user_id = $this->visitor->id;
    if(empty($user_id)){
      return $this->ajax_json('请先登录！', true); 
    }

    if(!isset($this->stash['array']) || empty($this->stash['array'])){
      return $this->ajax_json('请传入参数！', true); 
    }

    $cart_arr = explode('@@', $this->stash['array']);

    $cart_model = new Sher_Core_Model_Cart();
    $cart = $cart_model->load($user_id);
    if(empty($cart) || empty($cart['items'])){
      return $this->ajax_json('购物车为空！', true);    
    }

    foreach($cart_arr as $key=>$val){
      $val = explode('|', $val);
      $target_id = (int)$val[0];
      $type = (int)$val[1];

      // 批量删除
      foreach($cart['items'] as $k=>$v){
        if($v['target_id']==$target_id){
          unset($cart['items'][$k]);
        }
      }
    }// endfor

    $ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items'])));  
    $data = $cart_model->find_by_id($user_id);
    return $this->ajax_json('移除成功!', false, 0, $data);
  
  }

  /**
   * 加载购物车数量
   */
  public function ajax_fetch_count(){
		$user_id = $this->visitor->id;
    if(empty($user_id)){
      return $this->ajax_json('faile', true); 
    }
    $cart_model = new Sher_Core_Model_Cart();
    $cart = $cart_model->load($user_id);
    if(empty($cart)){
      $count = 0;
      return $this->ajax_json('faile', true);
    }else{
      $count = $cart['item_count'];
    }
    if(empty($count)){
      return $this->ajax_json('fail', true);   
    }else{
      return $this->ajax_json('success', 0, 0, array('count'=>$count));
    }
  
  }

    /**
     * 编辑购物车--只增减数量
     */
    public function edit_cart(){
 		$user_id = $this->visitor->id;
        if(empty($user_id)){
          return $this->ajax_json('请先登录！', true); 
        } 

        $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
        $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;
        $n = isset($this->stash['n']) ? (int)$this->stash['n'] : 1;


        if(empty($target_id) || empty($type) || empty($n)){
          return $this->ajax_json('请传入参数！', true); 
        }

        $cart_model = new Sher_Core_Model_Cart();
        $cart = $cart_model->load($user_id);
        if(empty($cart) || empty($cart['items'])){
          return $this->ajax_json('购物车为空!', true);
        }

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();

        // 批量更新数量
        foreach($cart['items'] as $k=>$v){
            if($v['target_id']==$target_id){
                $cart['items'][$k]['n'] = $n;
            }
        }

        $ok = $cart_model->update_set($user_id, array('items'=>$cart['items'], 'item_count'=>count($cart['items']))); 
        if(!$ok){
            return $this->ajax_json('更新失败!', true);    
        }

		$inventory_model = new Sher_Core_Model_Inventory();
		$product_model = new Sher_Core_Model_Product();

        if($type==2){
            $inventory = $inventory_model->load($target_id);
            if(empty($inventory)){
                return $this->ajax_json('产品未找到!', true); 
            }
            $price = $inventory['price'];
            
        }else{
            $product = $product_model->extend_load($target_id);
            if(empty($product)){
                return $this->ajax_json('产品未找到!', true);     
            }
            $price = $product['sale_price'];
        }

        $total_price = ((float)$price)*$n;

        return $this->ajax_json('success!', 0, 0, array('price'=>$price, 'total_price'=>$total_price, 'n'=>$n)); 
    }


}

