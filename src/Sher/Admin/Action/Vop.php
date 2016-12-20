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
		return $this->pool_list();
	}
	
	/**
     * 商品池
     * @return string
     */
    public function pool_list() {

        $this->set_target_css_state('pool');

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

        $this->set_target_css_state('product');

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
        $inventory_model = new Sher_Core_Model_Inventory();

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
            $p_result['data']['result']['sku_id'] = 0;
            $is_exist_product = $inventory_model->find_by_vop_id($sku);
            if(!empty($is_exist_product)){
                $p_result['data']['result']['sku_id'] = $is_exist_product['_id'];
                $p_result['data']['result']['product_id'] = $is_exist_product['product_id'];
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
        $this->set_target_css_state('product');

        $redirect_url = Doggy_Config::$vars['app.url.admin'].'/vop';
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        if(empty($id)){
            return $this->show_message_page("缺少请求参数！", $redirect_url);
        }

        $method = 'biz.product.detail.query';
        $response_key = 'biz_product_detail_query_response';
        $params = array('sku'=>$id);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->show_message_page($result['msg'].$result['code'], true);
        }
        if(empty($result['data']['success'])){
            return $this->show_message_page($result['data']['resultMessage'].$result['data']['code'], true);
        }

        $obj = $result['data']['result'];
        $obj['introduction'] = htmlspecialchars($obj['introduction']);

        $method = 'biz.product.skuImage.query';
        $response_key = 'biz_product_skuImage_query_response';
        $params = array('sku'=>$id);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->show_message_page($result['msg'].$result['code'], true);
        }
        if(empty($result['data']['success'])){
            return $this->show_message_page($result['data']['resultMessage'].$result['data']['code'], true);
        }
        $obj['images'] = $result['data']['result'][$id];
        //print_r($obj);exit;

        $this->stash['product'] = $obj;
        return $this->to_html_page('admin/vop/product_view.html'); 
    }

    /**
     * 订单列表
     */
    public function order_list(){
        $this->set_target_css_state('order');

        $redirect_url = Doggy_Config::$vars['app.url.admin'].'/vop';
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $page = isset($this->stash['page']) ? (int)$this->stash['page'] : 1;
        $size = isset($this->stash['pageSize']) ? $this->stash['pageSize'] : 20;
        $evt = $this->stash['evt'] = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;

        $yesterday = date("Y-m-d",strtotime("-1 day"));
        $date = $this->stash['start_date'] = isset($this->stash['start_date']) ? $this->stash['start_date'] : $yesterday;

        if($evt==0){    // 对账
            $method = 'biz.order.checkNewOrder.query';
            $response_key = 'biz_order_checkNewOrder_query_response';
        }elseif($evt==1){   // 妥投
            $method = 'biz.order.checkDlokOrder.query';
            $response_key = 'biz_order_checkDlokOrder_query_response';       
        }elseif($evt==2){   // 拒收
            $method = 'biz.order.checkRefuseOrder.query';
            $response_key = 'biz_order_checkRefuseOrder_query_response';        
        }

        $params = array('page'=>$page, 'date'=>$date);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->show_message_page($result['msg'].$result['code'], true);
        }
        if(empty($result['data']['success'])){
            //return $this->show_message_page($result['data']['resultMessage'].$result['data']['code'], true);
            return $this->to_html_page('admin/vop/order_list.html');
        }

        $this->stash['orders'] = $result['data']['result'];
        //print_r($result['data']['result']);
        return $this->to_html_page('admin/vop/order_list.html'); 
    
    }


    /**
     * 订单详情
     */
    public function order_view(){

        $this->set_target_css_state('order');

        $redirect_url = Doggy_Config::$vars['app.url.admin'].'/vop';
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        if(empty($id)){
            return $this->show_message_page("缺少请求参数！", $redirect_url);
        }

        $method = 'biz.order.jdOrder.query';
        $response_key = 'biz_order_jdOrder_query_response';
        $params = array('jdOrderId'=>$id);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->show_message_page($result['msg'].$result['code'], true);
        }
        if(empty($result['data']['success'])){
            return $this->show_message_page($result['data']['resultMessage'].$result['data']['code'], true);
        }

        $this->stash['order'] = $result['data']['result'];

        // 物流信息
        $method = 'biz.order.orderTrack.query';
        $response_key = 'biz_order_orderTrack_query_response';
        $params = array('jdOrderId'=>$id);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->show_message_page($result['msg'].$result['code'], true);
        }
        if(empty($result['data']['success'])){
            return $this->show_message_page($result['data']['resultMessage'].$result['data']['code'], true);
        }

        $this->stash['track'] = $result['data']['result'];

        //print_r($result['data']['result']);
        return $this->to_html_page('admin/vop/order_view.html');
    
    }

    /**
     * 余额明细
     */
    public function balance_list(){
         $this->set_target_css_state('balance');

        $redirect_url = Doggy_Config::$vars['app.url.admin'].'/vop';
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $page = isset($this->stash['pageNum']) ? $this->stash['pageNum'] : 1;
        $size = isset($this->stash['pageSize']) ? $this->stash['pageSize'] : 20;


        $method = 'biz.price.balancedetail.get';
        $response_key = 'biz_price_balancedetail_get_response';
        $params = array('pageNum'=>$page, 'pageSize'=>$size);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->show_message_page($result['msg'].$result['code'], true);
        }
        if(empty($result['data']['success'])){
            return $this->show_message_page($result['data']['resultMessage'].$result['data']['code'], true);
        }

        $this->stash['balances'] = $result['data']['result'];
        $pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/vop/balance_list?pageNum=%s&size=%d&page=#p#', $pageNum, $size);
        //print_r($result['data']['result']);
        return $this->to_html_page('admin/vop/balance_list.html'); 
    
    }

    /**
     * 消息列表
     * 建议处理方式：调用5.1查询消息后，本地保存，调用5.2删除已获取消息（不删除则一直查询到最前面的100条消息）
     */
    public function message_list(){
        $this->set_target_css_state('message');

        $redirect_url = Doggy_Config::$vars['app.url.admin'].'/vop';
        $id = isset($this->stash['id']) ? $this->stash['id'] : null;
        $type = $this->stash['type'] = isset($this->stash['type']) ? $this->stash['type'] : 0;
        if(empty($type)){
            $type = "1,2,4,5,6,10,11,12,13,14,15,16,17";
        }

        $method = 'biz.message.get';
        $response_key = 'biz_message_get_response';
        $params = array('type'=>$type);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->show_message_page($result['msg'].$result['code'], true);
        }
        if(empty($result['data']['success'])){
            return $this->show_message_page($result['data']['resultMessage'].$result['data']['code'], true);
        }

        //print_r($result['data']['result']);
        for($i=0;$i<count($result['data']['result']);$i++){
            $r = $result['data']['result'][$i]['result'];
            $result['data']['result'][$i]['result_json'] = $r;
            if(is_array($r)){
                $result['data']['result'][$i]['result_json'] = json_encode($r);
            }
        }

        $this->stash['messages'] = $result['data']['result'];
        return $this->to_html_page('admin/vop/message_list.html'); 
    }

    /**
     * 售后列表
     * 需要该配送单已经妥投。
     * 需要先调用3.3接口校验订单中某商品是否可以提交售后服务
     * 需要先调用3.4接口查询支持的服务类型
     * 需要先调用3.5接口查询支持的商品返回京东方式
     */
    public function server_list(){
        $this->set_target_css_state('server');

        $redirect_url = Doggy_Config::$vars['app.url.admin'].'/vop';
        $jd_order_id = isset($this->stash['jd_order_id']) ? $this->stash['jd_order_id'] : null;
        $page = isset($this->stash['pageNum']) ? $this->stash['pageNum'] : 1;
        $size = isset($this->stash['pageSize']) ? $this->stash['pageSize'] : 20;


        $method = 'biz.afterSale.serviceListPage.query';
        $response_key = 'biz_afterSale_serviceListPage_query_response';
        $params = array('param'=>array('jdOrderId'=>$jd_order_id, 'pageIndex'=>$page, 'pageSize'=>$size));
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->show_message_page($result['msg'].$result['code'], true);
        }
        if(empty($result['data']['success'])){
            return $this->show_message_page($result['data']['resultMessage'].$result['data']['code'], true);
        }

        $this->stash['servers'] = $result['data']['result'];
        return $this->to_html_page('admin/vop/server_list.html'); 
    }

    /**
     * ajax 申请售后
     */
    public function apply_server(){

        $params = array(
            'param'=>array(
                'jdOrderId' => "43454135570",   // 43486942134
                'customerExpect' => 10,
                'questionDesc' => 'test',
                'asCustomerDto' => array(
                    'customerContactName' => "田帅",
                    'customerTel' => '15001120509',
                    'customerMobilePhone' => '15001120509',
                    'customerEmail' => '',
                    'customerPostcode' => '',
                ),
                'asPickwareDto' => array(
                    'pickwareType' => 4,
                    'pickwareProvince' => 0,
                    'pickwareCity' => 0,
                    'pickwareCounty' => 0,
                    'pickwareVillage' => 0,
                    'pickwareAddress' => '酒仙桥北路 798 751 太火鸟',
                ),
                'asReturnwareDto' => array(
                    'returnwareType' => 10,
                    'returnwareProvince' => 0,
                    'returnwareCity' => 0,
                    'returnwareCounty' => 0,
                    'returnwareVillage' => 0,
                    'returnwareAddress' => '酒仙桥北路 798 751 太火鸟',
                ),
                'asDetailDto' => array(
                    'skuId' => '2206820',   // 1978183
                    'skuNum' => 1,
                ),
            ),
        );
        
        $method = 'biz.afterSale.afsApply.create';
        $response_key = 'biz_afterSale_afsApply_create_response';
        $params = $params;
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->ajax_json($result['msg'].$result['code'], true);
        }
        print_r($result);
        if(empty($result['data']['success'])){
            return $this->ajax_json($result['data']['resultMessage'].$result['data']['code'], true);
        }
        return $this->ajax_json('success', false, 0, array('balance_price'=>$result['data']['result']));
    }


    /*
     * ajax 查询余额
     */
    public function ajax_search_price_balance(){

        $method = 'biz.price.balance.get';
        $response_key = 'biz_price_balance_get_response';
        $params = array('payType'=>4);
        $json = !empty($params) ? json_encode($params) : '{}';
        $result = Sher_Core_Util_Vop::fetchInfo($method, array('param'=>$json, 'response_key'=>$response_key));

        if(!empty($result['code'])){
            return $this->ajax_json($result['msg'].$result['code'], true);
        }
        if(empty($result['data']['success'])){
            return $this->ajax_json($result['data']['resultMessage'].$result['data']['code'], true);
        }

        return $this->ajax_json('success', false, 0, array('balance_price'=>$result['data']['result']));
    
    }


    /**
     * 商品导出
     */
    public function export_product(){
    
        $this->set_target_css_state('product');

        $pageNum = isset($this->stash['pageNum']) ? $this->stash['pageNum'] : null;

        $redirect_url = Doggy_Config::$vars['app.url.domain'].'/admin';

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

        if(empty($total_count)){
            return $this->show_message_page('数据不存在!', $redirect_url); 
        }

		// 设置不超时
		set_time_limit(0);
			
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="vop_products.csv"');
		header('Cache-Control: max-age=0');
		
		// 打开PHP文件句柄，php://output表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

    	// Windows下使用BOM来标记文本文件的编码方式 
    	fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 输出Excel列名信息
		$head = array('skuID', '标题', '品牌', '协议价', '京东价', '产地', '是否入库', '是否下架');

		// 将数据通过fputcsv写到文件句柄
		fputcsv($fp, $head);

        $page = 1;
        $size = 50;
        $is_end = false;
        $counter = 0;
        $limit = 1000;

        $product_model = new Sher_Core_Model_Product();
        $inventory_model = new Sher_Core_Model_Inventory();

        while(!$is_end){

            $pnum = ceil($total_count / $size); //总页数，ceil()函数用于求大于数字的最小整数

            //用array_slice(array,offset,length) 函数在数组中根据条件取出一段值;array(数组),offset(元素的开始位置),length(组的长度)
            $newarr = array_slice($product_arr, ($page-1)*$size, $size);

            if(empty($newarr)){
                $is_end = true;
                break;
            }

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


            for($i=0;$i<count($newarr);$i++){

				$counter ++;
				if($limit == $counter){
					ob_flush();
					flush();
					$counter = 0;
				}

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

                $p_result['data']['result']['state_label'] = $p_result['data']['result']['state']=='0' ? '是' : '否';

                $p_result['data']['result']['price'] = isset($prices[$sku]) ? $prices[$sku] : array();

                $p_result['data']['result']['storaged'] = '否';
                $p_result['data']['result']['product_id'] = 0;
                $p_result['data']['result']['sku_id'] = 0;
                $is_exist_product = $inventory_model->find_by_vop_id($sku);
                if(!empty($is_exist_product)){
                    $p_result['data']['result']['sku_id'] = $is_exist_product['_id'];
                    $p_result['data']['result']['product_id'] = $is_exist_product['product_id'];
                    $p_result['data']['result']['storaged'] = '是';
                }

                $d = $p_result['data']['result'];
				$row = array($d['sku'], $d['name'], $d['brandName'], $d['price']['price'], $d['price']['jdPrice'], $d['productArea'], $d['storaged'], $d['state_label']);
				
				fputcsv($fp, $row);

            }   // endfor

            $page++;
		}
		
		fclose($fp);
    
    }

}

