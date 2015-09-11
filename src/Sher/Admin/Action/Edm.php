<?php
/**
 * Edm管理
 * @author purpen
 */
class Sher_Admin_Action_Edm extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
    'id' => 0,
		'page' => 1,
		'size' => 20,
    'kind' => 2,
	);
	
	public function _init() {
		$this->set_target_css_state('page_edm');
    }
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->message();
	}
	
	/** 
	 * emd列表
	 */
	public function edm() {
		$this->set_target_css_state('edm');
    $this->stash['kind'] = 1;
		
		return $this->to_html_page('admin/edm/list.html');
	}

	/** 
	 * message通知列表
	 */
	public function message() {
		$this->set_target_css_state('message');
    $this->stash['kind'] = 2;
		
		return $this->to_html_page('admin/edm/list.html');
	}

	/**
	 * 编辑内容
	 */
	public function edit(){
		$id = (int)$this->stash['id'];
		$row = array();
    if(!empty($id)){
      $this->stash['mode'] = 'edit';
			$edm = new Sher_Core_Model_Edm();
			$row = $edm->load((int)$id);
    }else{
      $this->stash['mode'] = 'create';
    }
		
		$this->stash['edm'] = $row;
		
		return $this->to_html_page('admin/edm/edit.html');
	}
	
	/**
	 * 开始发送
	 */
	public function send(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数错误', true);
		}
		$edm = new Sher_Core_Model_Edm();
		$row = $edm->load($id);
		if(empty($row) || $row['state'] > Sher_Core_Model_Edm::STATE_WAITING){
			return $this->ajax_note('请求操作有误，请核对！', true);
		}
		
		// 更新等待发送状态
		$ok = $edm->mark_set_wait($id); 
		if($ok){
			// 设置发送任务
      if($row['kind']==1){
			  Resque::enqueue('edming', 'Sher_Core_Jobs_Edm', array('edm_id' => $id));
      }elseif($row['kind']==2){
			  Resque::enqueue('notice', 'Sher_Core_Jobs_Notice', array('edm_id' => $id));
      }
		}

    if($row['kind']==1){
		  $redirect_url = Doggy_Config::$vars['app.url.admin'].'/edm/edm';
    }elseif($row['kind']==2){
		  $redirect_url = Doggy_Config::$vars['app.url.admin'].'/edm/message';
    }
		
		return $this->ajax_note('发送设置成功！');
	}
	
	/**
	 * 测试发送
	 */
	public function test(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->ajax_note('请求参数错误', true);
		}
		$edm = new Sher_Core_Model_Edm();
		$row = $edm->extend_load($id);
		if(empty($row)){
			return $this->ajax_note('请求操作有误，请核对！', true);
		}
		
    if($row['kind']==1){
      if(empty($row['test_user'])){
        return $this->ajax_note('缺少测试用户地址！', true);
      }
      
      $mg = new Mailgun\Mailgun('key-6k-1qi-1gvn4q8dpszcp8uvf-7lmbry0');
      Doggy_Log_Helper::debug("Mailgun to send test email!");
      $domain = 'email.taihuoniao.com';
      
      $result = $mg->sendMessage($domain, array(
        'from' => '太火鸟 <noreply@email.taihuoniao.com>',
        'to' => $row['test_user'],
        'subject' => $row['title'],
        'html' => $row['mailbody'],
      ));

    }elseif($row['kind']==2){
    
    }

		
		return $this->ajax_note('测试发送成功！');
	}
	
	/**
	 * 保存邮件
	 */
	public function save() {
		// 验证数据
		if(empty($this->stash['title']) || empty($this->stash['summary']) || empty($this->stash['mailbody'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		$edm = new Sher_Core_Model_Edm();
        
        $data = array();
        $data['title'] = $this->stash['title'];
        $data['kind'] = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
        $data['test_user'] = $this->stash['test_user'];
        $data['summary']   = $this->stash['summary'];
        $data['mailbody']  = $this->stash['mailbody'];
        
        $id = $this->stash['_id'];
        
		if(empty($id)){
			$ok = $edm->apply_and_save($data);
		}else{
            $data['_id'] = (int)$id;
			$ok = $edm->apply_and_update($data);
		}
		
		if(!$ok){
			return $this->ajax_note('数据保存失败,请重新提交', true);
		}
		
    if($data['kind']==1){
		  $redirect_url = Doggy_Config::$vars['app.url.admin'].'/edm/edm';
    }elseif($data['kind']==2){
		  $redirect_url = Doggy_Config::$vars['app.url.admin'].'/edm/message';
    }
		
		return $this->ajax_notification('保存成功.', false, $redirect_url);
	}
	
	/**
	 * 删除
	 */
	public function delete() {
		$id = (int)$this->stash['id'];
		if(!empty($id)){
			$edm = new Sher_Core_Model_Edm();
			$edm->remove($id);
		}
		$this->stash['id'] = $id;
		
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
}
