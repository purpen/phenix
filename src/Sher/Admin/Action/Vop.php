<?php
/**
 * 京东开普勒管理
 * @author tianshuai
 */
class Sher_Admin_Action_Vop extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => 0,
		'page' => 1,
		'size' => 20,
	);

	public function _init() {
		// 判断左栏类型
		$this->stash['show_type'] = "product";
    	$this->set_target_css_state('page_vop');
    }
	
	public function execute(){
		return $this->get_list();
	}
	
	/**
     * 商品池
     * @return string
     */
    public function pool_list() {

        $redirect_url = Doggy_Config::$vars['app.url.domain'].'/admin';

        $method = 'biz.product.PageNum.query';
        $response_key = 'biz_product_PageNum_query_response';
        $params = array();
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->show_message_page(sprintf("[%d]%s", $result['code'], $result['msg']), $redirect_url);      
        }

        //print_r($result);
		
        $this->stash['pools'] = $result;
        return $this->to_html_page('admin/vop/pool_list.html');
    }
	
	/**
	 * 商品列表
	 */
    public function product_list(){

        $pageNum = isset($this->stash['pageNum']) ? $this->stash['pageNum'] : null;
        $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
        $size = isset($this->stash['size']) ? (int)$this->stash['size'] : 8;

        $redirect_url = Doggy_Config::$vars['app.url.domain'].'/admin';

        $pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/vop/product_list?pageNum=%s&size=%d&page=#p#', $pageNum, $size);

        $method = 'biz.product.sku.query';
        $response_key = 'biz_product_sku_query_response';
        $params = array('pageNum'=>$pageNum);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->show_message_page(sprintf("[%d]%s", $result['code'], $result['msg']), $redirect_url);      
        }

        $products = $result['data']['result'];
        $product_arr = explode(',', $products);
        $total_count = count($product_arr);

        $pnum = ceil($total_count / $size); //总页数，ceil()函数用于求大于数字的最小整数

        //用array_slice(array,offset,length) 函数在数组中根据条件取出一段值;array(数组),offset(元素的开始位置),length(组的长度)
        $newarr = array_slice($product_arr, ($page-1)*$size, $size);

        // 获取价格
        $price_method = 'biz.price.sellPrice.get';
        $price_response_key = 'biz_price_sellPrice_get_response';
        
        $price_sku_arr = array();
        for($i=0;$i<count($newarr);$i++){
            array_push($price_sku_arr, sprintf("J_%s", $newarr[$i]));
        }
        $price_skus = implode(',', $price_sku_arr);
        $price_params = json_encode(array('sku'=>$price_skus));
        $price_result = Sher_Core_Util_Vop::fetchInfo($price_method, array('param'=>$price_params, 'response_key'=>$price_response_key));

        if(!empty($price_result['code'])){
            return $this->show_message_page(sprintf("[%d]%s", $price_result['code'], $price_result['msg']), $redirect_url);      
        }

        $prices = array();
        for($i=0;$i<count($price_result['data']['result']);$i++){
            $p = $price_result['data']['result'][$i];
            $prices[$p['skuId']] = array('price'=>$p['price'], 'jdPrice'=>$p['jdPrice']);
        }

        $product_model = new Sher_Core_Model_Product();

        $products = array();
        for($i=0;$i<count($newarr);$i++){
            // 获取商品详细信息
            $sku = $newarr[$i];
            $p_method = 'biz.product.detail.query';
            $p_response_key = 'biz_product_detail_query_response';
            $p_params = array('sku'=>$sku);
            $p_json = !empty($p_params) ? json_encode($p_params) : '{}';
            $p_result = Sher_Core_Util_Vop::fetchInfo($p_method, array('param'=>$p_json, 'response_key'=>$p_response_key));
            if(!empty($p_result['code']) && !empty($p_result['data']['success'])){
                continue;
            }

            $p_result['data']['result']['price'] = isset($prices[$sku]) ? $prices[$sku] : array();

            $p_result['data']['result']['storaged'] = 0;
            $p_result['data']['result']['product_id'] = 0;
            $is_exist_product = $product_model->find_by_vop_id($sku);
            if(!empty($is_exist_product)){
                $p_result['data']['result']['product_id'] = $is_exist_product['_id'];
                $p_result['data']['result']['storaged'] = 1;
            }

            array_push($products, $p_result['data']['result']);

        }   // endfor
		
        $this->stash['products'] = $products;
        $this->stash['total_count'] = $total_count;
        $this->stash['pnum'] = $pnum;
        $this->stash['pager_url'] = $pager_url;

        return $this->to_html_page('admin/vop/product_list.html');
        
    }


    /**
     * 商品详情
     */
    public function product_view(){
    
    }


}

