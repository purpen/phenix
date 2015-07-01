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
	
	protected $exclude_method_list = array('execute', 'coupon', 'active','tool','member','yuyue','choose','ok','volunteer');
	
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
	
}

