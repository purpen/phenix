<?php
/**
 * 评测试用管理
 * @author purpen
 */
class Sher_Admin_Action_Try extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
    'user_id' => '',
    'is_invented' => -1,
    'q' => '',
    'sort' => 0,
    'result' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_try');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/try?page=#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/try/list.html');
	}
	
	/**
	 * 编辑器参数
	 */
	protected function editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = new MongoId();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_ASSET;
	}
	
	/**
	 * 新增评测
	 */
	public function edit() {
		
		$this->stash['mode'] = 'create';
		
		$data = array();
		if (!empty($this->stash['id'])){
			$model = new Sher_Core_Model_Try();
			$data = $model->load((int)$this->stash['id']);
			
			$this->stash['mode'] = 'edit';
		}
		$this->stash['try'] = $data;
		
        // 主图上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_TRY;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_TRY;

        // 配图上传参数
		$this->stash['token_f'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid_f'] = new MongoId();
		
		$this->stash['asset_type_f'] = Sher_Core_Model_Asset::TYPE_TRY_F;
		
		$this->editor_params();
		
		return $this->to_html_page('admin/try/edit.html');
	}
	
	/**
	 * 保存评测
	 */
	public function save() {
		// 验证数据
		if(empty($this->stash['title']) || empty($this->stash['content'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
    }

    $imgs = array();
    if(!empty($this->stash['try_brand_avatar'])){
      $imgs['brand_avatar'] = $this->stash['try_brand_avatar'];
    }
    if(!empty($this->stash['try_qr_ios'])){
      $imgs['qr_ios'] = $this->stash['try_qr_ios'];
    }
    if(!empty($this->stash['try_qr_android'])){
      $imgs['qr_android'] = $this->stash['try_qr_android'];
    }

    $data = array();
    $data['title'] = $this->stash['title'];
    $data['short_title'] = $this->stash['short_title'];
    $data['season'] = (int)$this->stash['season'];
    $data['description'] = $this->stash['description'];
    $data['brand_introduce'] = $this->stash['brand_introduce'];
    $data['cover_id'] = $this->stash['cover_id'];
    $data['banner_id'] = $this->stash['banner_id'];
    $data['content'] = $this->stash['content'];
    $data['step_stat'] = (int)$this->stash['step_stat'];
    $data['start_time'] = $this->stash['start_time'];
    $data['end_time'] = $this->stash['end_time'];
    $data['publish_time'] = $this->stash['publish_time'];
    $data['try_count'] = (int)$this->stash['try_count'];
    $data['apply_term'] = (int)$this->stash['apply_term'];
    $data['term_count'] = (int)$this->stash['term_count'];
    $data['imgs'] = $imgs;

		$model = new Sher_Core_Model_Try();

		if(empty($this->stash['_id'])){
			// 发起人
			$data['user_id'] = (int)$this->visitor->id;
			
			$ok = $model->apply_and_save($data);
			
			$data = $model->get_data();
			$id = $data['_id'];
    }else{
      $data['_id'] = (int)$this->stash['_id'];
			$ok = $model->apply_and_update($data);
			
			$id = $this->stash['_id'];
		}
		
		if(!$ok){
			return $this->ajax_note('数据保存失败,请重新提交', true);
		}
		
		// 上传成功后，更新所属的附件
		if(isset($this->stash['asset']) && !empty($this->stash['asset'])){
			$model->update_batch_assets($this->stash['asset'], (int)$id);
		}

		// 上传成功后，更新所属的附件
		if(isset($this->stash['asset_f']) && !empty($this->stash['asset_f'])){
			$model->update_batch_assets($this->stash['asset_f'], (int)$id);
		}
		
		$next_url = Doggy_Config::$vars['app.url.admin_base'].'/try';
		
		return $this->ajax_notification('评测保存成功.', false, $next_url);
	}
	
	/**
	 * 删除评测
	 */
	public function delete() {
		if(!empty($this->stash['id'])){
			$model = new Sher_Core_Model_Try();
			$model->remove((int)$this->stash['id']);
		}
		
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	/**
	 * 确认发布
	 */
	public function publish() {
		return $this->update_state(Sher_Core_Model_Try::STATE_PUBLISH);
	}
	
	/**
	 * 确认撤销发布
	 */
	public function unpublish() {
		return $this->update_state(Sher_Core_Model_Try::STATE_DRAFT);
	}
	
	/**
	 * 确认发布评测
	 */
	protected function update_state($state) {
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Try();
			$model->mark_as_publish((int)$this->stash['id'], $state);
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('请求操作失败，请检查后重试！', true);
		}
		
		$this->stash['state'] = $state;
		
		return $this->to_taconite_page('admin/publish_ok.html');
	}
	
	/**
	 * 查看申请人数
	 */
	public function verify() {
		if(empty($this->stash['id'])){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/try/verify?id=%d&is_invented=%d&user_id=%d&result=%d&sort=$d&page=#p#';
		
		$id = (int)$this->stash['id'];

		$this->stash['pager_url'] = sprintf($pager_url, $id, $this->stash['is_invented'], $this->stash['user_id'], $this->stash['result'], $this->stash['sort']);
		
		$model = new Sher_Core_Model_Try();
		$try = &$model->extend_load($id);
		
		if(empty($try)){
			return $this->show_message_page('访问的公测产品不存在或已被删除！', $redirect_url);
		}
		
		// 增加pv++
		$model->increase_counter('view_count', 1, $id);
		
		$this->stash['try'] = &$try;
		
		return $this->to_html_page('admin/try/verify.html');
	}
	
	/**
	 * 通过审核
	 */
	public function pass(){
		$this->stash['approved'] = true;
		return $this->verify_result(Sher_Core_Model_Apply::RESULT_PASS);
	}
	
	/**
	 * 驳回审核
	 */
	public function reject(){
		$this->stash['approved'] = false;
		return $this->verify_result(Sher_Core_Model_Apply::RESULT_REJECT);
	}
	
	/**
	 * 审核状态
	 */
	protected function verify_result($result, $id=null){
		if (is_null($id)){
			$id = $this->stash['id'];
		}
		if (is_null($result) || empty($id)){
			return $this->ajax_notification('缺少请求参数！', true);
		}
		
		try{
			$apply = new Sher_Core_Model_Apply();
			$row = $apply->find_by_id($id);
			if(empty($row)){
				return $this->ajax_notification('该申请不存在或已被删除！', true);
			}
			$apply_user_id = $row['user_id'];
			$try_id = (int)$row['target_id'];
			// 更新状态
			$ok = $apply->mark_set_result($id, $result);
			
			$is_add = ($result == Sher_Core_Model_Apply::RESULT_PASS) ? 1 : 0;
			// 同步更新公测
			if ($ok) {
				$try = new Sher_Core_Model_Try();
				$try->update_pass_users($try_id, $apply_user_id, $is_add);
			}
		} catch (Sher_Core_Model_Exception $e){
			return $this->ajax_notification('申请审核操作失败，请检查后重试！', true);
		}
		
		
		
		return $this->to_taconite_page('admin/verify_ok.html');
	}

  /**
   * 支持名单
   */
  public function vote_list(){
    $pager_url = Doggy_Config::$vars['app.url.admin_base'].'/try/vote_list?apply_id=%s&page=#p#';
		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['apply_id']);
 		return $this->to_html_page('admin/try/vote_list.html'); 
  }

	/**
	 * 推荐/取消推荐
	 */
	public function ajax_set_stick() {

		if(empty($this->stash['id'])){
			return $this->ajax_note('缺少请求参数！', true);
		}
    $evt = $this->stash['evt'] = isset($this->stash['evt']) ? $this->stash['evt'] : 0;
		
		try{
			$model = new Sher_Core_Model_Try();
      if($evt){
        $model->mark_as_stick((int)$this->stash['id']);     
      }else{
        $model->mark_cancel_stick((int)$this->stash['id']);
      }
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_note('请求操作失败，请检查后重试！', true);
		}
		
		return $this->to_taconite_page('admin/try/stick_ok.html');
	}

  /**
   * 导出申请人资料
   */
  public function apply_export(){
  
		$query = array();
		$options = array();
		$page = 1;
		$size = 500;

    $is_invented = isset($this->stash['is_invented']) ? (int)$this->stash['is_invented'] : 0;
    $result = isset($this->stash['result']) ? (int)$this->stash['result'] : 0;
    $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
    $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;

    if($target_id){
      $query['target_id'] = $target_id;   
    }

    if($result){
      if($result==-1){
        $query['result'] = 0;
      }elseif($result==1){
        $query['result'] = 1;
      }
    }
    $query['type'] = 1;
    $query['is_invented'] = array('$ne'=>1);
		
		if(empty($query)){
			return $this->ajax_json('请选择导出数据条件！', true);
		}
		
		// 设置不超时
		set_time_limit(0);
			
		 header('Content-Type: application/vnd.ms-excel');
		 header('Content-Disposition: attachment;filename="user_info.csv"');
		 header('Cache-Control: max-age=0');
		
    //Windows下使用BOM来标记文本文件的编码方式 -解决windows下乱码
    //fwrite($export_file, chr(0xEF).chr(0xBB).chr(0xBF)); 
		// 打开PHP文件句柄，php://output表示直接输出到浏览器
     $fp = fopen('php://output', 'a');

    	// Windows下使用BOM来标记文本文件的编码方式 
    	fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 输出Excel列名信息
		$head = array('ID', '姓名', '电话', '地址', '邮编', '微信', 'QQ', '支持数', '申请内容');
		foreach($head as $i => $v){
			// CSV的Excel支持GBK编码，一定要转换，否则乱码
			// $head[$i] = iconv('utf-8', 'gbk', $v);
		}
		// 将数据通过fputcsv写到文件句柄
		fputcsv($fp, $head);
		
		$service = Sher_Core_Service_Apply::instance();
		
		$is_end = false;
		$counter = 0;
		$limit = 1000;
        $options['size'] = $size;
		$options['sort_field'] = 'latest';
		
		while(!$is_end){
			$options['page'] = $page;
			
			Doggy_Log_Helper::warn("Export try apply page[$page],size[$size]!");
			
			$result = $service->get_list($query, $options);
			
			$max = count($result['rows']);
			for($i=0; $i<$max; $i++){
				$counter ++;
				if($limit == $counter){
					ob_flush();
					flush();
					$counter = 0;
				}
				
        $apply = $result['rows'][$i];
        $address = sprintf("%s-%s-%s", $apply['area_province']['city'], $apply['area_district']['city'], $apply['address']);
				$row = array($apply['user_id'], $apply['name'], $apply['phone'], $address, $apply['zip'], $apply['wx'], $apply['qq'], $apply['vote_count'], $apply['content']);
				
				/*
				foreach($row as $k => $v){
					// CSV的Excel支持GBK编码，一定要转换，否则乱码
					// $row[$i] = iconv('utf-8', 'gbk', $v);
				}*/
				
				fputcsv($fp, $row);
				
				unset($row);
			}
			
			if($max < $size){
				$is_end = true;
				break;
			}
			
			$page++;
		}
		
		fclose($fp);
  
  }
	
	
}

