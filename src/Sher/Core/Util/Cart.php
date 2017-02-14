<?php
/**
 * 购物车的工具类
 * 
 * 采用cookie的方式实现购物车的过程,cookie字符串记录产品的id与购买数量.
 * 
 * @author purpen
 * @version $Id$
 */
class Sher_Core_Util_Cart extends Doggy_Object {
    
    const LIFE_TIME = 864000; // 10day
    
    public $com_item = 0;
    public $com_list = array();
    public $comlist_count = 0;
    
    public function __construct(){
    	$this->com_item = 0;
    	$this->comlist_count = 0;
    	
    	$this->get();
    }
    /**
     * 将购买的商品设置到购物车cookie
     * 
     * @param $data
     * @return void
     */
    public function set(){
        $ttl = time() + self::LIFE_TIME;
        
        $value = $this->buildData();
		
		Doggy_Log_Helper::warn("Set the cart to cookie: [$value]");
		
        $this->setCookie($value, $ttl);
    }
	
    /**
     * 获取购物车里的商品
     * 
     * @param bool $raw
     * @return mixed
     */
    public function get(){
        $data = $this->getCookie();
        $this->parseData($data);
    }
	
	/**
	 * 获取购物车产品列表
	 */
	public function getItems(){
		return $this->com_list;
	}
	
    /**
     * 清空购物车
     * 
     * @return void
     */
    public function emptyCart(){
		$this->com_item = 0;
		$this->com_list = array();
		$this->comlist_count = 0;
        
        $this->clearCookie();
    }
	
    /**
     * 清空cookie
     * 
     * @return void
     */
    public function clearCookie(){
        @setcookie('_THN_USER_CCK_', '', time() - self::LIFE_TIME, '/');
    }
	
    /**
     * 设置cookie
     * 
     * @param string $key
     * @param string $value
     * @param $ttl
     * @return void
     */
    public function setCookie($value, $ttl){
         setcookie('_THN_USER_CCK_', $value, $ttl, '/');
    }
	
    /**
     * 获取cookie的值
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getCookie($default=null){
        return isset($_COOKIE['_THN_USER_CCK_']) ? $_COOKIE['_THN_USER_CCK_'] : $default;
    }
    
    /**
     * 将数组转化成字符串
     * 
     * @param $data
     * @return string
     */
    public function buildData(){
        $result = array();
        if(empty($this->com_list)){
        	return;
        }
		
        foreach($this->com_list as $item){
        	$result[] = "{$item['sku']},{$item['quantity']}";
        }
		
        //购物车商品数不能超过100个
        if(count($result) > 100){
        	array_splice($result, -100);
        }
		
        return implode('[]', $result);
    }
	
    /**
     * 将字符串转化成数组
     * 
     * @param $data
     * @return array
     */
    public function parseData($data){
        if(!empty($data)){
            $result = explode('[]', $data);
            
            for($i=0; $i<count($result); $i++){
                $item = $result[$i];
                list($com_sku, $com_num) = explode(",", $item);
                if(!empty($com_sku) && !empty($com_num)){
                	$this->addItem($com_sku);
                	$this->setItemQuantity($com_sku, $com_num);
                }
            }
            
        }
    }
	
    /**
     * 添加明细项到购物车中
     * 
     * @param $com_sku
     * @param $com_size
     * @return void
     */
    public function addItem($com_sku){
        $com_has_exist = 0;
        if($this->com_item > 0){
            for($i=0; $i<$this->com_item; $i++){
                if($this->com_list[$i]['sku'] == $com_sku){
                    $this->com_list[$i]['quantity']++;
					// 更新单品小计
					$this->com_list[$i]['subtotal'] = $this->com_list[$i]['quantity'] * $this->com_list[$i]['sale_price'];
                    $com_has_exist = 1;
                }
            }
        }
        
        if($com_has_exist != 1){
			// 获取sku信息
			$inventory = new Sher_Core_Model_Inventory();
			$item = $inventory->load((int)$com_sku);

            // 推广码
            $referral_code = isset($_COOKIE['referral_code']) ? $_COOKIE['referral_code'] : '';
            $storage_id = isset($this->stash['storage_id']) ? (string)$this->stash['storage_id'] : '';
            $vop_id = null;
            $number = '';

            if(!empty($item)){
                $com_pid = $item['product_id'];
                $vop_id = isset($item['vop_id']) ? $item['vop_id'] : null;
                $number = $item['number'];
            }else{
                $com_pid = (int)$com_sku;
            }
			
			// 获取单品信息
            $product = new Sher_Core_Model_Product();
            $row = $product->extend_load((int)$com_pid);
			
            if(!empty($row)){
				$count = 1;
				$com_title  = !empty($item) ? $row['title'].'（'.$item['mode'].'）' : $row['title'];
				$true_price = !empty($item) ? $item['price'] : $row['sale_price'];
        $type = !empty($item) ? 2 : 1;
        $sku_mode = !empty($item) ? $item['mode'] : null;
                $this->com_list[] = array('sku'=>$com_sku,'product_id'=>$com_pid, 'quantity'=>(int)$count, 'type'=>$type, 'sku_mode'=>$sku_mode, 'price'=>$true_price,'sale_price'=>$true_price,'title'=>$com_title,'cover'=>$row['cover']['thumbnails']['mini']['view_url'],'view_url'=>$row['view_url'],'subtotal'=>$count*$row['sale_price'], 'vop_id'=>$vop_id, 'number'=>(string)$number, 'referral_code'=>$referral_code, 'storage_id'=>$storage_id);
                $this->com_item++;
            }
            unset($product);
			unset($inventory);
        }
    }
	
    /**
     * 从购物车中删除某项
     * 
     * @param $com_sku
     * @param $com_size
     * @return void
     */
    public function delItem($com_sku){
    	if($this->com_item <= 0){
    		return;
    	}
		
		Doggy_Log_Helper::debug("Remove from the cart [$com_sku]");
		$index = 0;
		$exist = false;
        for($i=0; $i<$this->com_item; $i++){
            if($this->com_list[$i]['sku'] == $com_sku){
            	$this->com_item--;
            	$index = $i;
				$exist = true;
            }
        }
		
		if ($exist){
			// 删除元素
			array_splice($this->com_list, $index, 1);
		}
		
        //检测购物车是否为空
        if($this->com_item <= 0){
			$this->com_item = 0;
			$this->com_list = array();
			$this->comlist_count = 0;
            
            $this->clearCookie();
        }
    }
	
    /**
     * 获取购物车某个商品
     * 
     * @param $com_sku
     * @param $com_size
     * @return void
     */
    public function findItem($com_sku){
        if($this->com_item <= 0){
            return;
        }
		
        for($i=0; $i<$this->com_item; $i++){
            if($this->com_list[$i]['sku'] == $com_sku){
                return $this->com_list[$i];
            }
        }
    }
	
    /**
     * 更新购物车中商品的数量
     * 
     * @param $com_sku
     * @param $com_size
     * @param $com_num
     * @return void
     */
    public function setItemQuantity($com_sku, $com_count){
    	if(!empty($com_sku)){
    	    for($i=0; $i<$this->com_item; $i++){
                if($this->com_list[$i]['sku'] == $com_sku){
                    if ($com_count <= 0){ //如果数量为负数时,直接清除该商品
                    	$this->com_item--;
                    	unset($this->com_list[$i]);
                    }else{
                    	$this->com_list[$i]['quantity'] = (int)$com_count;
                    }
					
					// 更新单品小计
					$this->com_list[$i]['subtotal'] = $this->com_list[$i]['quantity'] * $this->com_list[$i]['sale_price'];
                }   
            }
    	}
		
    	//检测购物车是否为空
    	if($this->com_item <= 0){
			$this->com_item = 0;
			$this->com_list = array();
			$this->comlist_count = 0;
    		
    		$this->clearCookie();
    	}
    }
	
    /**
     * 获取购物车总金额数
     * 
     * @return float
     */
    public function getTotalAmount(){
    	$total_amount = 0;
    	for($i=0; $i<$this->com_item; $i++){
    		$total_amount += $this->com_list[$i]['quantity']*$this->com_list[$i]['sale_price'];
    	}
        return $total_amount;
    }
	
    /**
     * 获取购物车商品总数
     * 
     * @return int
     */
    public function getItemCount(){
        /**
        for($i=0; $i<$this->com_item; $i++){
            $this->comlist_count += $this->com_list[$i]['quantity'];
        }
         */
        $this->comlist_count = $this->com_item;
		Doggy_Log_Helper::debug("Cart item count: ".$this->comlist_count);
		
        return $this->comlist_count;
    }
    
}
/**vim:sw=4 et ts=4 **/

