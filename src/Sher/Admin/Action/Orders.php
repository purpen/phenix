<?php
/**
 * 后台订单管理
 * @author purpen
 */
class Sher_Admin_Action_Orders extends Sher_Admin_Action_Base {
	
	public $stash = array(
		'id' => 0,
		'page' => 1,
		'size' => 50,
		's' => 0,
		'q' => '',
        'from_site' => '',
	);
	
	public function execute(){
		
		// 判断左栏类型
		$this->stash['show_type'] = "sales";
		
		return $this->get_list();
	}
	
	/**
	 * 订单高级搜索
	 */
	public function search(){		
		
		$params = array();
		if(!empty($this->stash['q'])){
			$params['q'] = $this->stash['q'];
		}
		if(!empty($this->stash['name'])){
			$params['name'] = $this->stash['name'];
		}
		if(!empty($this->stash['mobile'])){
			$params['mobile'] = $this->stash['mobile'];
		}
		if(!empty($this->stash['product'])){
			$params['product'] = $this->stash['product'];
		}
		if(!empty($this->stash['sku'])){
			$params['sku'] = $this->stash['sku'];
		}
		if(!empty($this->stash['start_date'])){
			$params['start_date'] = $this->stash['start_date'];
			$this->stash['start_time'] = strtotime($this->stash['start_date']);
		}
		if(!empty($this->stash['end_date'])){
			$params['end_date'] = $this->stash['end_date'];
			$this->stash['end_time'] = strtotime($this->stash['end_date']);
		}
		if(!empty($this->stash['s'])){
			$params['s'] = $this->stash['s'];
		}

		if(!empty($this->stash['from_site'])){
			$params['from_site'] = $this->stash['from_site'];
		}
		
		$arg = "";
		while(list($key, $val) = each($params)){
			$arg .= $key."=".$val."&";
		}
		// 去掉最后一个&字符
		$arg = substr($arg, 0, count($arg)-2);
		// 去除转义
		$arg = stripslashes($arg);
		
		// 处理分页链接
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/orders/search?';
		if(empty($arg)){
			$this->stash['pager_url'] = $pager_url.'page=#p#';
		}else{
			$this->stash['pager_url'] = $pager_url.$arg.'&page=#p#';
		}
		
		return $this->to_html_page('admin/orders/search.html');
	}
	
	/**
	 * 导出订单列表
	 */
	public function export(){
		$query = array();
		$options = array();
		$page = 1;
		$size = 500;
		
		if(!empty($this->stash['s'])){
			$status = $this->stash['s'];
			switch($status){
				case 1: // 未支付订单
					$query['status'] = Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT;
					break;
				case 2: // 待发货订单
					$query['status'] = Sher_Core_Util_Constant::ORDER_READY_GOODS;
					break;
				case 3: // 已发货订单
					$query['status'] = Sher_Core_Util_Constant::ORDER_SENDED_GOODS;
					break;
				case 4: // 已完成订单
					$query['status'] = Sher_Core_Util_Constant::ORDER_PUBLISHED;
					break;
				case 5: // 申请退款订单
					$query['status'] = Sher_Core_Util_Constant::ORDER_READY_REFUND;
					break;
				case 6: // 已退款订单
					$query['status'] = Sher_Core_Util_Constant::ORDER_REFUND_DONE;
					break;
				case 9: // 已关闭订单：取消的订单、过期的订单
					$query['status'] = array(
						'$in' => array(Sher_Core_Util_Constant::ORDER_EXPIRED, Sher_Core_Util_Constant::ORDER_CANCELED),
					);
					break;
			}
		}
		
		if(!empty($this->stash['q'])){
			$query['rid'] = $this->stash['q'];
		}
		if(!empty($this->stash['name'])){
			$query['name'] = $this->stash['name'];
		}
		if(!empty($this->stash['mobile'])){
			$query['mobile'] = $this->stash['mobile'];
		}
		if(!empty($this->stash['product'])){
			if($this->stash['product']){
				$searcher = Sher_Core_Service_Search::instance();
	            $query_words = $searcher->check_query_string($this->stash['product']);
	            if(!empty($query_words)){
					if(count($query_words) == 1){
	                    $query['full'] = $query_words[0];
	                }
	                else {
	                    $query['full']['$all'] = $query_words;
	                }
	            }
			}
		}
		if(!empty($this->stash['sku'])){
			$query['sku'] = (int)$this->stash['sku'];
		}
		if(!empty($this->stash['start_date']) && !empty($this->stash['end_date'])){
			$query['created_on'] = array('$gte' => strtotime($this->stash['start_date']), '$lte' => strtotime($this->stash['end_date']));
		}
		if(!empty($this->stash['start_date']) && empty($this->stash['end_date'])){
			$query['created_on'] = array('$gte' => strtotime($this->stash['start_date']));
		}
		if(empty($this->stash['start_date']) && !empty($this->stash['end_date'])){
			$query['created_on'] = array('$lte' => strtotime($this->stash['end_date']));
		}
		
		if(empty($query)){
			return $this->ajax_json('请选择导出数据条件！', true);
		}
		
		$filepath = Doggy_Config::$vars['app.storage.tmpdir'];
		$filename = 'frbird_report_'.date('YmdH').'.csv';
		
		$export_file = $filepath.'/'.$filename;
		// 检测是否已经存在该文件
		if(is_file($export_file)){
			//return $this->ajax_json('一个小时内已导出过此数据！', true);
		}
		
		// 设置不超时
		set_time_limit(0);
			
		// header('Content-Type: application/vnd.ms-excel');
		// header('Content-Disposition: attachment;filename="'.$export_file.'"');
		// header('Cache-Control: max-age=0');
		
    //Windows下使用BOM来标记文本文件的编码方式 -解决windows下乱码
    fwrite($export_file, chr(0xEF).chr(0xBB).chr(0xBF)); 
		// 打开PHP文件句柄，php://output表示直接输出到浏览器
		$fp = fopen($export_file, 'w');

    	// Windows下使用BOM来标记文本文件的编码方式 
    	fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 输出Excel列名信息
		$head = array('下单时间', '订单付款时间', '订单编号', '买家会员名', '买家支付方式', '宝贝标题', '宝贝种类', '宝贝总数量', '总金额', '实际支付金额', '订单状态', '买家留言', '收货人姓名', '联系手机', '收货地址', '运送方式', '物流单号', '物流公司', '是否要发票', '发票类型', '发票抬头', '订单备注');
		foreach($head as $i => $v){
			// CSV的Excel支持GBK编码，一定要转换，否则乱码
			// $head[$i] = iconv('utf-8', 'gbk', $v);
		}
		// 将数据通过fputcsv写到文件句柄
		fputcsv($fp, $head);
		
		$service = Sher_Core_Service_Orders::instance();
		
		$is_end = false;
		$counter = 0;
		$limit = 1000;
        $options['size'] = $size;
		$options['sort_field'] = 'positive';
		
		while(!$is_end){
			$options['page'] = $page;
			
			Doggy_Log_Helper::warn("Export order page[$page],size[$size]!");
			
			$result = $service->get_search_list($query, $options);
			
			$max = count($result['rows']);
			for($i=0; $i<$max; $i++){
				$counter ++;
				if($limit == $counter){
					ob_flush();
					flush();
					$counter = 0;
				}
				
				$data = $result['rows'][$i]['order'];
				// 收货人地址
				if(!empty($data['express_info'])){
					$name = $data['express_info']['name'];
					$mobile = $data['express_info']['phone'];
					$address = $data['express_info']['province'].' '.$data['express_info']['city'].' '.$data['express_info']['area'].' '.$data['express_info']['address'];
				}else{
					$name = $data['addbook']['name'];
					$mobile = $data['addbook']['phone'];
					$address = $data['addbook']['area_province']['city'].' '.$data['addbook']['area_district']['city'].' '.$data['addbook']['address'];
				}
				
				$payed_date = !empty($data['payed_date']) ? date('Y-m-d H:i:s', $data['payed_date']) : '';
				
				$express_company = !empty($data['express_caty']) ? $data['express_company']['title'] : '';
				$express_away = '快递';
				
				// 宝贝信息
				$titles = array();
				$quantity = 0;
				foreach($data['items'] as $item){
					$quantity += $item['quantity'];
					$titles[] = Sher_Core_Util_Shopping::get_product_title($item['sku'], $item['product_id']);
				}
				$product_title = implode(',', $titles);
				
				// 是否需要发票
				if($data['invoice_type'] == 1){
					$need_invoice = 'Yes';
					$invoice_caty_label = $data['invoice_caty_label']['title'];
					$invoice_content = $data['invoice_title'];
				}else{
					$need_invoice = 'No';
					$invoice_caty_label = '';
					$invoice_content = '';
				}
				
				$row = array(date('Y-m-d H:i:s', $data['created_on']), $payed_date, $data['rid'], $data['user']['nickname'], $data['payment']['name'], $product_title, $data['items_count'], $quantity, $data['total_money'], $data['pay_money'], $data['status_label'], $data['summary'], $name, $mobile, $address, $express_away, $data['express_no'], $express_company, $need_invoice, $invoice_caty_label, $invoice_content, '');
				
				/*
				foreach($row as $k => $v){
					// CSV的Excel支持GBK编码，一定要转换，否则乱码
					// $row[$i] = iconv('utf-8', 'gbk', $v);
				}*/
				
				fputcsv($fp, $row);
				
				unset($row);
				unset($data);
			}
			
			if($max < $size){
				$is_end = true;
				break;
			}
			
			$page++;
		}
		
		fclose($fp);
		
		$export_url = Doggy_Config::$vars['app.url.domain'].'/export/'.$filename;
		
		return $this->ajax_json('数据导出成功！', false, '/', array('export_url' => $export_url));
	}
	
	
	/**
	 * 更新导入
	 */
	public function update_import(){}
	
	/**
     * 订单列表
     */
    public function get_list() {
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/orders?s=%d&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s']);
		
		$this->stash['admin'] = true;
		
		$this->set_target_css_state('page_orders');
		
        return $this->to_html_page('admin/orders/list.html');
    }
	
	/**
     * 订单详情
     */
	public function show() {
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);

        // 是否可拆单
        $order_info['is_split'] = false;
        if((!isset($order_info['exist_sub_order']) || $order_info['exist_sub_order']==0) && $order_info['items_count']>1){

            // 可拆单的状态
            $arr = array(
                Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT,
                Sher_Core_Util_Constant::ORDER_READY_GOODS, 
            );
            if(in_array($order_info['status'], $arr)){
                $order_info['is_split'] = true;
            }
            
        }
		$this->stash['order_info'] = $order_info;

        // 申请退款的订单才允许退款操作(包括已发货,确认收货,完成操作)
        $this->stash['can_refund'] = Sher_Core_Helper_Order::refund_order_status_arr($order_info['status']);
		
		$this->set_target_css_state('page_orders');
		
		return $this->to_html_page("admin/orders/view.html");
	}
	
	/**
	 * 点击发货
	 */
	public function send(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		
		$order_info = $model->find_by_rid($rid);
		    
        if(empty($order_info)){
            return $this->show_message_page('订单不存在！', true);       
        }

        // 存在子订单
        if(isset($order_info['exist_sub_order']) && !empty($order_info['exist_sub_order'])){
            for($i=0;$i<count($order_info['sub_orders']);$i++){
                if(!empty($order_info['sub_orders'][$i]['is_sended'])){
                    $order_info['sub_orders'][$i]['express_company'] = $model->find_express_category($order_info['sub_orders'][$i]['express_caty']);
                }
            }
        } 

		$express_caty = $model->find_express_category();
		
		$this->stash['order_info'] = $order_info;
		$this->stash['express_caty'] = $express_caty;
		
		return $this->to_html_page("admin/orders/send.html");
	}
	
	/**
	 * 关闭订单
	 */
	public function ajax_close_order(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->ajax_notification('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 检查是否具有权限
		if (!$this->visitor->can_admin()) {
			return $this->ajax_notification('操作不当，你没有权限关闭！', true);
		}
		
		// 未支付订单才允许关闭
		if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT){
			return $this->ajax_notification('该订单出现异常，请联系客服！', true);
		}
        $jd_order_id = isset($order_info['jd_order_id']) ? $order_info['jd_order_id'] : null;
		try {
			// 关闭订单
			$model->close_order($order_info['_id'], array('user_id'=>$order_info['user_id'], 'jd_order_id'=>$jd_order_id));
        } catch (Sher_Core_Model_Exception $e) {
            return $this->ajax_notification('关闭订单失败:'.$e->getMessage(),true);
        }
		
		$this->stash['order'] = $model->find_by_rid($rid);
		$this->stash['admin'] = true;
		
		return $this->to_taconite_page('ajax/order_ok.html');
	}

	/**
	 * 退款后关闭订单（忽略库存）
	 */
	public function ajax_close_order_and_ignore_inventory(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->ajax_json('操作不当，请查看购物帮助！', true);
		}
		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		
		// 检查是否具有权限
		if (!$this->visitor->can_admin()) {
			return $this->ajax_json('操作不当，你没有权限关闭！', true);
		}
		
		// 待发货订单才允许关闭
		if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_READY_GOODS){
			return $this->ajax_json('该订单状态不允许操作！', true);
		}

        // 验证该订单是否全部完成退款
        for($i=0;$i<count($order_info['items']);$i++){
            $item = $order_info['items'][$i];
            if(!isset($item['refund_type']) || $item['refund_status']!=2){
  			    return $this->ajax_json('该订单下商品没有完成退款操作！', true);              
            }
        }

		try {
			// 关闭订单
			$model->close_order_and_ingore_inventory((string)$order_info['_id'], array('user_id'=>$order_info['user_id']));
        } catch (Sher_Core_Model_Exception $e) {
            return $this->ajax_json('关闭订单失败:'.$e->getMessage(),true);
        }
		
		return $this->ajax_json('success', false, null, array('rid'=>$rid));
	}
	
	/**
	 * 撤销发货
	 */
	public function revoke_send(){
		$rid = $this->stash['rid'];
		
		try{
			if (empty($rid)) {
				return $this->ajax_note('参数缺少！', true);
			}
			
			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($rid);
			
			// 仅已付款订单，可发货
			if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_SENDED_GOODS) {
				return $this->ajax_note('订单还未发货！', true);
			}
			
			$ok = $model->revoke_order_sended((string)$order_info['_id']);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('撤销订单发货失败：'.$e->getMessage(), true);
		}
		
		$view_url = Doggy_Config::$vars['app.url.admin'].'/orders/show?rid='.$rid;
		
		return $this->ajax_note('撤销成功！', false, $view_url);
	}
	
	/**
	 * 更新发货状态
	 */
	public function update_send(){
		$rid = $this->stash['rid'];
        $sub_order_id = isset($this->stash['sub_order_id']) ? $this->stash['sub_order_id'] : null;
		$express_caty = $this->stash['express_caty'];
		$express_no = $this->stash['express_no'];

        // 是否更新父订单状态
        $all_sended = true;
		
		if (empty($rid) || empty($express_caty) || empty($express_no)) {
			return $this->ajax_json('参数缺少！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Orders();
			$order_info = $model->find_by_rid($rid);

            if(empty($order_info)){
 				return $this->ajax_json('订单未找到！', true);               
            }
			
			// 仅待发货订单
			if ($order_info['status'] != Sher_Core_Util_Constant::ORDER_READY_GOODS) {
				//return $this->ajax_json('订单非待发货状态！', true);
            }

            // 存在子订单
            if(isset($order_info['exist_sub_order']) && !empty($order_info['exist_sub_order'])){
                if(empty($sub_order_id)){
 				    return $this->ajax_json('子订单不存在！', true);
                }
                // 更新子订单状态
                for($i=0;$i<count($order_info['sub_orders']);$i++){
                    if($order_info['sub_orders'][$i]['id']==$sub_order_id){
                        $order_info['sub_orders'][$i]['is_sended'] = 1;
                        $order_info['sub_orders'][$i]['express_caty'] = $express_caty;
                        $order_info['sub_orders'][$i]['express_no'] = $express_no;
                        $order_info['sub_orders'][$i]['sended_on'] = time();
                    }else{
                        if(empty($order_info['sub_orders'][$i]['is_sended'])){
                            $all_sended = false;
                        }
                    }
                }
                $sub_ok = $model->update_set((string)$order_info['_id'], array('sub_orders'=>$order_info['sub_orders']));
                if(!$sub_ok){
  				    return $this->ajax_json('更新子订单物流失败！', true);                   
                }
            }
			
            if($all_sended){
                $ok = $model->sended_order((string)$order_info['_id'], array('express_caty'=>$express_caty, 'express_no'=>$express_no, 'user_id'=>$order_info['user_id']));
            }else{
                $ok = false;
            }

              // 短信提醒用户
              if($ok){
                $order_message = sprintf("亲爱的伙伴：我们已将您编号为（%s）的宝贝托付到有颜靠谱的快递小哥手中，希望Fiu为您带去更新鲜的生活方式和更奇妙的生活体验。", $order_info['rid']);
                $order_phone = $order_info['express_info']['phone'];
                if(!empty($order_phone)){
                  Sher_Core_Helper_Util::send_yp_defined_fiu_mms($order_phone, $order_message);
                }
              }
			
            /**
			// 微信订单，调用发货通知
			if ($ok && $order_info['from_site'] == Sher_Core_Util_Constant::FROM_WEIXIN) {
				// 获取openid
				$user = new Sher_Core_Model_User();
				$user_info = $user->load((int)$order_info['user_id']);
				if (empty($user_info)){
					return $this->show_message_page('订单['.$rid.']用户不存在！', true);
				}
				$openid = $user_info['wx_open_id'];
				
				$options = array(
					'token' => Doggy_Config::$vars['app.wechat.token'],
					'appid' => Doggy_Config::$vars['app.wechat.app_id'],
					'appsecret' => Doggy_Config::$vars['app.wechat.app_secret'],
					'partnerid' => Doggy_Config::$vars['app.wechat.partner_id'],
					'partnerkey' => Doggy_Config::$vars['app.wechat.partner_key'],
					'paysignkey' => Doggy_Config::$vars['app.wechat.paysign_key'],
				);
				$wechat = new Sher_Core_Util_Wechat($options);
				
				$transid = $order_info['trade_no'];
				$out_trade_no =$order_info['rid'];
				$status = 1;
				
				Doggy_Log_Helper::warn("Wechat order[$rid] send goods notice!");
				
				$result = $wechat->sendPayDeliverNotify($openid, $transid, $out_trade_no, $status);
				if (!$result || $result['errcode'] != 0 || $result['errcode'] != 'ok'){
					Doggy_Log_Helper::warn("Wechat order[$rid] send goods failed: ".json_encode($result));
					return $this->show_message_page('订单['.$rid.']更新失败！', true);
				}
            }
            **/
		}catch(Sher_Core_Model_Exception $e){
			return $this->show_message_page('更新订单发货失败：'.$e->getMessage(), true);
		}
		
		return $this->ajax_json('操作成功！', false, '', array('sub_order_id'=>$sub_order_id, 'rid'=>$rid));
	}

  /**
   * 确认退款操作
   */
  public function ajax_do_refund(){
 		
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->ajax_notification('订单不存在！', true);
		}

		// 检查是否具有权限---有问题
		if (!$this->visitor->can_admin()) {
			return $this->ajax_notification('操作不当，你没有权限！', true);
		}

		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);
		//订单不存在
		if(empty($order_info)){
				return $this->ajax_notification('订单未找到！', true);     
		}
		
    // 申请退款的订单才允许退款操作(包括已发货,确认收货,完成操作)
		if (!Sher_Core_Helper_Order::refund_order_status_arr($order_info['status'])){
			return $this->ajax_notification('订单状态不正确！', true);
		}

		// 跳转支付宝退款
		if ($order_info['trade_site'] == Sher_Core_Util_Constant::TRADE_ALIPAY){
            if($order_info['from_app']==2){
 			    $refund_url = Doggy_Config::$vars['app.url.alipay'].'/fiu_refund?rid='.$rid;           
            }else{
			    $refund_url = Doggy_Config::$vars['app.url.alipay'].'/refund?rid='.$rid;
            }
			return $this->to_redirect($refund_url);
		}

		// 跳转京东退款
		if ($order_info['trade_site'] == Sher_Core_Util_Constant::TRADE_JDPAY){
			$refund_url = Doggy_Config::$vars['app.url.domain'].'/app/site/jdpay/refund?rid='.$rid;
			return $this->to_redirect($refund_url);
		}
		
		// 跳转微信支付退款
		if ($order_info['trade_site'] == Sher_Core_Util_Constant::TRADE_WEIXIN){
          // 如果是来自app,则跳转app退款页面(微信的网页支付和app支付没有共用sdk)
          if(in_array($order_info['from_site'], array(Sher_Core_Util_Constant::FROM_IAPP, Sher_Core_Util_Constant::FROM_APP_ANDROID))){
                  $refund_url = Doggy_Config::$vars['app.url.jsapi.wxpay'].'/fiu_refund?rid='.$rid;
          }else{
                  $refund_url = Doggy_Config::$vars['app.url.jsapi.wxpay'].'/refund?rid='.$rid;
          }
			return $this->to_redirect($refund_url);
		}
		
		return $this->show_message_page('只支持支付宝退款', true);	
	}

  /**
   * 确认退款操作
   */
  public function ajax_new_refund(){
 		
		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		if (empty($id)) {
			return $this->ajax_notification('缺少请求参数！', true);
		}

		// 检查是否具有权限---有问题
		if (!$this->visitor->can_admin()) {
			return $this->ajax_notification('操作不当，你没有权限！', true);
		}

        $refund_model = new Sher_Core_Model_Refund();
        $refund = $refund_model->load($id);
        if(empty($refund)){
 		    return $this->ajax_notification('退款单不存在！', true);
        }

        if($refund['stage'] != Sher_Core_Model_Refund::STAGE_ING){
  		    return $this->ajax_notification('退款单状态不符！', true);
        }

		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($refund['order_rid']);
		//订单不存在
		if(empty($order_info)){
			return $this->ajax_notification('订单未找到！', true);
		}

        $rid = $order_info['rid'];

		// 跳转支付宝退款
		if ($order_info['trade_site'] == Sher_Core_Util_Constant::TRADE_ALIPAY){
            if($order_info['from_app']==2){
 			    $refund_url = Doggy_Config::$vars['app.url.alipay'].'/fiu_refund?id='.$id;           
            }else{
			    $refund_url = Doggy_Config::$vars['app.url.alipay'].'/refund?id='.$id;
            }
			return $this->to_redirect($refund_url);
		}

		// 跳转京东退款
		if ($order_info['trade_site'] == Sher_Core_Util_Constant::TRADE_JDPAY){
			$refund_url = Doggy_Config::$vars['app.url.domain'].'/app/site/jdpay/refund?id='.$id;
			return $this->to_redirect($refund_url);
		}
		
		// 跳转微信支付退款
		if ($order_info['trade_site'] == Sher_Core_Util_Constant::TRADE_WEIXIN){
          // 如果是来自app,则跳转app退款页面(微信的网页支付和app支付没有共用sdk)
          if(in_array($order_info['from_site'], array(Sher_Core_Util_Constant::FROM_IAPP, Sher_Core_Util_Constant::FROM_APP_ANDROID))){
                  $refund_url = Doggy_Config::$vars['app.url.jsapi.wxpay'].'/fiu_refund?id='.$id;
          }else{
                  $refund_url = Doggy_Config::$vars['app.url.jsapi.wxpay'].'/refund?id='.$id;
          }
			return $this->to_redirect($refund_url);
		}
		
		return $this->show_message_page('只支持支付宝退款', true);	
	}

  /**
   * 强制退款操作－不退款，更改订单状态，用于非支付宝支付的订单或需要人工退款操作的
   */
  public function ajax_do_refund_force(){
 		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->ajax_notification('订单不存在！', true);
		}
		
		// 检查是否具有权限---有问题
		if (!$this->visitor->can_admin()) {
			return $this->ajax_notification('操作不当，你没有权限！', true);
    }

		$model = new Sher_Core_Model_Orders();
		$order_info = $model->find_by_rid($rid);

    //订单不存在
    if(empty($order_info)){
 			return $this->ajax_notification('订单未找到！', true);
    }

    // 申请退款的订单才允许退款操作(包括已发货,确认收货,完成操作)
		if (!Sher_Core_Helper_Order::refund_order_status_arr($order_info['status'])){
			return $this->ajax_notification('订单状态不正确！', true);
    }

    $ok = $model->refunded_order($order_info['_id'], array('refunded_price'=>$order_info['pay_money']));
    if($ok){
		  return $this->ajax_json('操作成功', false, '', array('is_error'=>false, 'message'=>'操作成功'));
    }else{
 		  return $this->ajax_json('操作失败', false, '', array('is_error'=>true, 'message'=>'操作失败'));  
    }
  
  }

    /**
     * ajax 拆单
     */
    public function ajax_split_order(){
        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
        $val = isset($this->stash['val']) ? $this->stash['val'] : null;
        if(empty($rid) || empty($val)){
            return $this->ajax_json('缺少请求参数!', true);
        }
		$model = new Sher_Core_Model_Orders();
		$order = $model->find_by_rid($rid);
        if(empty($order)){
            return $this->ajax_json('订单不存在!', true);       
        }

        // 可拆单的状态
        $arr = array(
            Sher_Core_Util_Constant::ORDER_WAIT_PAYMENT,
            Sher_Core_Util_Constant::ORDER_READY_GOODS, 
        );
        if(!in_array($order['status'], $arr)){
            return $this->ajax_json('该订单状态不允许拆分操作!', true);
        }

        $arr = explode(',',$val);
        if(count($arr)==1){
            return $this->ajax_json('至少拆分两个订单!', true);       
        }
        $sub_orders = array();
        for($i=0;$i<count($arr);$i++){
            $sub_order = array();
            $d = explode(':',$arr[$i]);
            if(count($d)!=2){
                return $this->ajax_json('参数结构不正确01!', true);
            }
            $sub_order['id'] = sprintf("%s-%d", $order['rid'], $d[0]);
            $sku_arr = explode(';', $d[1]);

            $items = array();
            for($j=0;$j<count($sku_arr);$j++){
                $sku_id = (int)$sku_arr[$j];
                if(empty($sku_id)){
                    return $this->ajax_json('参数结构不正确02!', true);
                }

                for($k=0;$k<count($order['items']);$k++){
                    if($order['items'][$k]['sku']==$sku_id){
                        $item = $order['items'][$k];
                        array_push($items, $item);
                        break;
                    }
                }
            }   // endfor
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
            return $this->ajax_json('无法获取子订单!', true);       
        }

        $ok = $model->update_set((string)$order['_id'], array('exist_sub_order'=>1 ,'sub_orders'=>$sub_orders));

        if(!$ok){
            return $this->ajax_json('拆单保存失败!', true);            
        }
        return $this->ajax_json('success', false, '', array());

    }

    /**
     * 发货前验证是否有退款
     */
    public function check_refund(){
        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
 	    $sub_order_id = isset($this->stash['sub_order_id']) ? $this->stash['sub_order_id'] : null;

        if(empty($rid)){
            return $this->ajax_json('缺少请求参数!', true);   
        }
		$model = new Sher_Core_Model_Orders();
		$order = $model->find_by_rid($rid);
        if(empty($order)){
            return $this->ajax_json('订单不存在!', true);
        }
        $has_refund = false;
        if(!empty($sub_order_id)){
            for($i=0;$i<count($order['sub_orders']);$i++){
                $sub_order = $order['sub_orders'][$i];
                if($sub_order['id'] == $sub_order_id){
                    for($j=0;$j<count($sub_order['items']);$j++){
                        $item = $sub_order['items'][$j];

                        if(isset($item['refund_type']) && !empty($item['refund_type'])){
                            $has_refund = true;
                            if($item['refund_status']==1){
                                return $this->ajax_json('先处理退款!', true);
                            }
                        }
                    }
                }

            }
        }else{
            for($i=0;$i<count($order['items']);$i++){
                $item = $order['items'][$i];
                if(isset($item['refund_type']) && !empty($item['refund_type'])){
                    $has_refund = true;
                    if($item['refund_status']==1){
                        return $this->ajax_json('先处理退款!', true);                      
                    }
                }
            }
        }

        return $this->ajax_json('success', false, null, array('has_refund'=>$has_refund));
    }

    /**
     * 强制发货
     */
    public function force_send(){
        $rid = isset($this->stash['rid']) ? $this->stash['rid'] : null;
        if(empty($rid)){
            return $this->ajax_json('缺少请求参数!', true);   
        } 

		$model = new Sher_Core_Model_Orders();
		$order = $model->find_by_rid($rid);
        if(empty($order)){
            return $this->ajax_json('订单不存在!', true);
        }

		// 待发货订单才允许发货
		if ($order['status'] != Sher_Core_Util_Constant::ORDER_READY_GOODS){
			return $this->ajax_json('订单状态不正确！', true);
		}

        $can_send = false;
        for($i=0;$i<count($order['items']);$i++){
            $item = $order['items'][$i];
            if(!isset($item['refund_type']) || empty($item['refund_type'])){
                $can_send = true;
                break;
            }
        }

        $express_caty = $express_no = null;
        if(isset($order['exist_sub_order']) && !empty($order['exist_sub_order'])){
            for($i=0;$i<count($order['sub_orders']);$i++){
                $sub_order = $order['sub_orders'][$i];
                if(isset($sub_order['express_no']) && !empty($sub_order['express_no'])){
                    $express_caty = $sub_order['express_caty'];
                    $express_no = $sub_order['express_no'];
                    break;
                }
            }
        }else{
            $express_caty = $order['express_caty'];
            $express_no = $order['express_no'];
        }

        if(empty($express_no)){
            return $this->ajax_json('没有物流信息!', true);           
        }

        $ok = $model->sended_order((string)$order['_id'], array('express_caty'=>$express_caty, 'express_no'=>$express_no, 'user_id'=>$order['user_id']));
        // 短信提醒用户
        if($ok){
            $order_message = sprintf("亲爱的伙伴：我们已将您编号为（%s）的宝贝托付到有颜靠谱的快递小哥手中，希望Fiu为您带去更新鲜的生活方式和更奇妙的生活体验。", $order['rid']);
            $order_phone = $order['express_info']['phone'];
            if(!empty($order_phone)){
                Sher_Core_Helper_Util::send_yp_defined_fiu_mms($order_phone, $order_message);
            }
        }

        return $this->ajax_json('success', false, '', array('rid'=>$rid));

    }

}
