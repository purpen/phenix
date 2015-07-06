<?php
/**
 * D3IN铟立方未来实验室
 * @author purpen
 */
class Sher_App_Action_D3in extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
    'size'=>50,
	);
	
	protected $exclude_method_list = array('execute', 'coupon', 'active','tool','member','yuyue','choose','ok','volunteer','buy');
	
	/**
	 * 网站入口
	 */
	public function execute(){
		return $this->d3in();
	}
	
	/**
	 * d3in
	 */
	public function d3in(){
    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $this->stash['vip_money'] = $vip_money;
		return $this->to_html_page('page/d3in/index.html');
	}
	
	/**
	 * d3in 活动
	 */
	public function active(){
    $this->set_target_css_state('sub_active');
		return $this->to_html_page('page/d3in/active.html');
	}
	
	/**
	 * d3in 活动
	 */
	public function tool(){
    $this->set_target_css_state('sub_device');
		return $this->to_html_page('page/d3in/tool.html');
	}
	
	/**
	 * d3in 会员
	 */
	public function member(){
    $this->set_target_css_state('sub_member');

    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $this->stash['vip_money'] = $vip_money;
		return $this->to_html_page('page/d3in/member.html');
	}
	
	/**
	 * d3in volunteer
	 */
	public function volunteer(){

		return $this->to_html_page('page/d3in/volunteer.html');
	}
	
	/**
	 * d3in 预约
	 */
	public function yuyue(){
    $this->set_target_css_state('sub_appoint');
		return $this->to_html_page('page/d3in/yuyue.html');
	}
	
	/**
	 * d3in 预约2
	 */
	public function choose(){
    $this->set_target_css_state('sub_appoint');
    $query = array();
    $options = array();
    $model = new Sher_Core_Model_Classify();

    $query['kind'] = Sher_Core_Model_Classify::KIND_D3IN;
    $query['pid'] = 0;
    $options['page'] = (int)$this->stash['page'];
    $options['size'] = (int)$this->stash['size'];
    $data = $model->find($query, $options);
    foreach($data as $key=>$val){
      $data[$key] = $model->extended_model_row($val);
      // 子类
      $children = $model->find(array('pid'=>$val['_id'], 'kind'=>Sher_Core_Model_Classify::KIND_D3IN));
      if($children){
        $data[$key]['children'] = $children;
      }else{
        $data[$key]['children'] = null;     
      }
    }

    $this->stash['classifies'] = $data;

		return $this->to_html_page('page/d3in/choose.html');
	}
	
	/**
	 * d3in 预约成功
	 */
	public function ok(){
    $this->set_target_css_state('sub_appoint');
		return $this->to_html_page('page/d3in/ok.html');
	}

  /**
   * 提交申请志愿者信息
   */
  public function volunteer_save(){
 		// 验证数据
		if(empty($this->stash['name']) || empty($this->stash['tel']) || empty($this->stash['email']) || empty($this->stash['position']) || empty($this->stash['content'])){
			return $this->ajax_note('信息不全！', true);
		}
		$mode = 'create';
		
		$data = array();
    $id = null;
		$data['title'] = '申请实验室志愿者';
		$data['content'] = $this->stash['content'];
    $data['name'] = $this->stash['name'];
		$data['tel'] = $this->stash['tel'];
    $data['email'] = $this->stash['email'];
    $data['sex'] = (int)$this->stash['sex'];
    $data['position'] = $this->stash['position'];
    $data['kind'] = 2;
		
		try{
			$model = new Sher_Core_Model_Contact();

      $has_record = $model->first(array('user_id'=>(int)$this->visitor->id, 'kind'=>2, 'state'=>array('$in'=>array(0,1))));
      if(!empty($has_record)){
 			  return $this->ajax_note('不能重复申请！', true);       
      }
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_note('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("申请失败：".$e->getMessage());
			return $this->ajax_json('申请保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.d3in'].'/volunteer';
		
    return $this->to_taconite_page('page/d3in/ajax_volunteer.html');
  
  }

  /**
   * 支付页面
   */
	public function buy(){
    $this->set_target_css_state('sub_member');

    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $this->stash['vip_money'] = $vip_money;

		return $this->to_html_page('page/d3in/buy.html');
	}

  /**
   * 下订单
   */
	public function pay(){
    $evt = isset($this->stash['evt'])?$this->stash['evt']:null;
		$redirect_url = Doggy_Config::$vars['app.url.d3in']."/buy";
		if(empty($evt)){
			return $this->show_message_page('缺少请求参数！', $redirect_url);
		}
		if(!in_array($evt, array('day', 'month', 'quarter', 'self_year', 'year'))){
			return $this->show_message_page('请求参数不正确！', $redirect_url);
		}

    $this->set_target_css_state('sub_member');

    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $this->stash['vip_money'] = $vip_money;
    $end_time = 0;
    switch($evt){
      case 'month':
        $server_str = sprintf("%0.1f元 / 月", $vip_money['month']);
        $end_time = date('Y-m-d', strtotime('1 month'));
        break;
      case 'quarter':
        $server_str = sprintf("%0.1f元 / 季 (88折)", $vip_money['quarter']);
        $end_time = date('Y-m-d', strtotime('3 month'));
        break;
      case 'self_year':
        $server_str = sprintf("%0.1f元 / 半年 (8折)", $vip_money['self_year']);
        $end_time = date('Y-m-d', strtotime('6 month'));
        break;
      case 'year':
        $server_str = sprintf("%0.1f元 / 全年 (8折)", $vip_money['year']);
        $end_time = date('Y-m-d', strtotime('12 month'));
        break;
    }
    $this->stash['begin_time'] = date('Y-m-d');
    $this->stash['server_str'] = $server_str;
    $this->stash['end_time'] = $end_time;

		return $this->to_html_page('page/d3in/pay.html');
	}

  /**
   * 生成订单
   */
  public function confirm(){
    $evt = isset($this->stash['evt'])?$this->stash['evt']:null;
    if(empty($evt)){
      return $this->ajax_json('缺少请求参数!', true);
    }

    if(!in_array($evt, array('day', 'month', 'quarter', 'self_year', 'year'))){
      return $this->ajax_json('请求参数不正确!', true);   
    }

    $vip_money = Doggy_Config::$vars['app.d3in.vip_money'];
    $data = array();
    $pay_money = 0;

    $pay_money = Sher_Core_Util_D3in::member_vip_info($evt, 'price');
    $item_id = Sher_Core_Util_D3in::member_vip_info($evt, 'item_id');
    $item_name = Sher_Core_Util_D3in::member_vip_info($evt, 'item_name');
    
    if(empty((float)$pay_money)){
      return $this->ajax_json('类型错误!', true);   
    }

    $data['item_id'] = $item_id;
    $data['item_name'] = $item_name;
    $data['pay_money'] = $pay_money;
    $data['total_money'] = $pay_money;
    $data['user_id'] = $this->visitor->id;
    $data['payment_method'] = 'a';

    try{
			// 生成订单
			$model = new Sher_Core_Model_DOrder();
      $ok = $model->apply_and_save($data);
      if(!$ok){
        return $this->ajax_json('创建订单失败!', true);
      }

			$data = $model->get_data();
			$rid = $data['rid'];

      $redirect_url = Doggy_Config::$vars['app.url.d3in'];

	    $redirect_url = Doggy_Config::$vars['app.url.d3in'].'/success?rid='.$rid;

		  return $this->ajax_json('保存成功.', false, $redirect_url);

    }catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Create d3in D-order failed: ".$e->getMessage());
      return $this->ajax_json('创建订单失败.!', true);  
    }

  }

	/**
	 * 下单成功，选择支付方式，开始支付
	 */
	public function success(){
		$rid = $this->stash['rid'];
		if (empty($rid)) {
			return $this->show_message_page('订单不正确！');
		}
		
		$model = new Sher_Core_Model_DOrder();
		$order_info = $model->find_by_rid($rid);
		
		$this->stash['order'] = $order_info;
		
		return $this->to_html_page('page/d3in/success.html');
	}

	/**
	 * 处理支付
	 */
	public function payed(){
		$rid = $this->stash['rid'];
		$payaway = $this->stash['payaway'];
		if (empty($rid)) {
			return $this->show_message_page('订单不存在！');
		}
		if (empty($payaway)){
			$next_url = Doggy_Config::$vars['app.url.d3in'].'/success?rid='.$rid;
			return $this->show_message_page('请至少选择一种支付方式！', $next_url, 2000);
		}
		
		$model = new Sher_Core_Model_DOrder();
		$order_info = $model->find_by_rid($rid);
		
		// 挑选支付机构
		Doggy_Log_Helper::warn('Pay away:'.$payaway);
		
		$pay_url = '';
		switch($payaway){
			case 'alipay':
				$pay_url = Doggy_Config::$vars['app.url.alipay'].'/d_payment?rid='.$rid;
				break;
			case 'tenpay':
				$pay_url = Doggy_Config::$vars['app.url.tenpay'].'/d_payment?rid='.$rid;
				break;
			default:
				// 网上银行支付
				$pay_url = Doggy_Config::$vars['app.url.alipay'].'/d_payment?rid='.$rid.'&bank='.$payaway;
				break;
		}
		
		return $this->to_redirect($pay_url);
	}
	
}

