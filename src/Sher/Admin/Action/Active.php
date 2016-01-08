<?php
/**
 * 活动管理
 * @author purpen
 */
class Sher_Admin_Action_Active extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'kind' => 0,
	);
	
	public function _init() {
		$this->set_target_css_state('page_active');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $kind = (int)$this->stash['kind'];
    switch($kind){
      case 1:
        $this->set_target_css_state('offical');
        break;
      case 2:
        $this->set_target_css_state('d3in');
        break;
      default:
        $this->set_target_css_state('all');
    }

		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/active?kind=%d&page=#p#', $kind);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/active/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;
		$mode = 'create';
		
		$model = new Sher_Core_Model_Active();

		if(!empty($id)){
			$mode = 'edit';
			$active = $model->find_by_id($id);
			$active = $model->extended_model_row($active);
			$this->stash['active'] = $active;
		}
		$this->stash['mode'] = $mode;
		//分类数组
		$this->stash['categories'] = $model->find_category();
		
		// 编辑器上传附件
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = new MongoId();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_ACTIVE;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_ACTIVE;

		// 活动头图，封面图上传
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();

		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_ACTIVE;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_ACTIVE;

		// 活动列表图上传
		$this->stash['list_pid'] = new MongoId();

		$this->stash['asset_type_active'] = Sher_Core_Model_Asset::TYPE_USER_ACTIVE;
		
		return $this->to_html_page('admin/active/submit.html');
	}

	/**
	 * 保存信息
	 */
	public function save(){		
		$id = isset($this->stash['id'])?(int)$this->stash['id']:0;

		$data = array();
		$data['title'] = $this->stash['title'];
		$data['sub_title'] = $this->stash['sub_title'];
		$data['season'] = !empty($this->stash['season'])?(int)$this->stash['season']:0;
		$data['kind'] = !empty($this->stash['kind'])?(int)$this->stash['kind']:1;
		$data['summary'] = $this->stash['summary'];
		$data['content'] = $this->stash['content'];
		$data['cover_id'] = $this->stash['cover_id'];
		$data['banner_id'] = $this->stash['banner_id'];
		$data['wap_banner_id'] = $this->stash['wap_banner_id'];
		$data['tags'] = $this->stash['tags'];
		$data['category_id'] = (int)$this->stash['category_id'];
		$data['free_stat'] = (int)$this->stash['free_stat'];
		$data['line_stat'] = (int)$this->stash['line_stat'];
		$data['begin_time'] = $this->stash['begin_time'];
		$data['end_time'] = $this->stash['end_time'];
		//$data['contact_name'] = $this->stash['contact_name'];
		//$data['contact_tel'] = $this->stash['contact_tel'];
		//$data['contact_email'] = $this->stash['contact_email'];
		$data['address'] = $this->stash['address'];
		$data['conduct_city'] = $this->stash['conduct_city'];
		$data['max_number_count'] = (int)$this->stash['max_number_count'];
		//$data['pay_money'] = (float)$this->stash['pay_money'];
		$data['step_stat'] = (int)$this->stash['step_stat'];
		//$data['stick'] = (int)$this->stash['stick'];
		$data['state'] = (int)$this->stash['state'];
		$data['report_url'] = $this->stash['report_url'];

    //地图信息
    $data['map_info'] = array();
    if(!empty($this->stash['map_x']) && !empty($this->stash['map_y']) && !empty($this->stash['map_matter'])){
      $map_info = array();
      $map_info['x'] = $this->stash['map_x']; 
      $map_info['y'] = $this->stash['map_y'];
      $map_info['matter'] = $this->stash['map_matter']; 
      $map_info['title'] = $this->stash['map_title']; 
      $map_info['img'] = $this->stash['map_img']; 

      $data['map_info'] = $map_info;     
    }

    //进度安排
    $data['process'] = array();
    if(isset($this->stash['process'])){
      $p_arr = array();
      foreach($this->stash['process'] as $process){
        $s_arr = array();
        $arr_process = explode('|', $process);
        $s_arr['sort'] = $arr_process[0];
        $s_arr['time'] = $arr_process[1];
        $s_arr['title'] = $arr_process[2];
        $s_arr['name'] = $arr_process[3];
        $s_arr['position'] = $arr_process[4];
        $s_arr['img'] = $arr_process[5];
        array_push($p_arr, $s_arr);
      }
      $data['process'] = $p_arr;
    }

    //合作伙伴
    $data['partner'] = array();
    if(isset($this->stash['partner'])){
      $p_arr = array();
      foreach($this->stash['partner'] as $partner){
        $s_arr = array();
        $arr_partner = explode('|', $partner);
        $s_arr['sort'] = $arr_partner[0];
        $s_arr['name'] = $arr_partner[1];
        $s_arr['url'] = $arr_partner[2];
        $s_arr['img'] = $arr_partner[3];
        array_push($p_arr, $s_arr);
      }
      $data['partner'] = $p_arr;
    }

		// 检查是否有附件
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
			$data['asset_count'] = count($data['asset']);
		}else{
			$data['asset'] = array();
			$data['asset_count'] = 0;
		}

		try{
			$model = new Sher_Core_Model_Active();
			
			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
				
				$id = $model->id;
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}
			// 上传成功后，更新所属的附件--头图，封面图，其它
			if(isset($this->stash['asset_tmp']) && !empty($this->stash['asset_tmp'])){
				$this->update_batch_assets($this->stash['asset_tmp'], $id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save block failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/active';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 批量更新附件所属
	 */
	protected function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				Doggy_Log_Helper::debug("Update asset[$id] parent_id: $parent_id");
				$model->update_set($id, array('parent_id' => $parent_id));
			}
			unset($model);
		}
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('活动不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Active();
			
			foreach($ids as $id){
				$active = $model->load((int)$id);
				
				if (!empty($active)){
					//逻辑删除
					$model->mark_remove((int)$id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

  /**
   * 搜索
   */
  public function search(){
    $this->stash['is_search'] = true;
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/active/search?s=%d&q=%s&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['s'], $this->stash['q']);
    return $this->to_html_page('admin/active/list.html');
  
  }

	/**
	 * 发布/撤销
	 */
	public function ajax_publish(){
		$ids = $this->stash['id'];
    $evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_Active();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));
		
		foreach($ids as $id){
			$result = $model->mark_as_published((int)$id, $evt);
		}
		
		$this->stash['note'] = '操作成功！';
		
		return $this->to_taconite_page('ajax/published_ok.html');
	}

  /**
   * 推荐／取消
   */
  public function ajax_stick(){
 		$ids = $this->stash['id'];
		$evt = isset($this->stash['evt'])?(int)$this->stash['evt']:0;
		if(empty($ids)){
			return $this->ajax_notification('缺少Id参数！', true);
		}
		
		$model = new Sher_Core_Model_Active();
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u',$ids)));
		
		foreach($ids as $id){
			$result = $model->mark_as_stick((int)$id, $evt);
		}
		
		$this->stash['note'] = '操作成功！';
		
		return $this->to_taconite_page('ajax/published_ok.html');
  
  }

  public function get_attend_list(){
		$page = (int)$this->stash['page'];
    $this->stash['event'] = isset($this->stash['event'])?$this->stash['event']:1;
    $this->stash['target_id'] = isset($this->stash['target_id'])?$this->stash['target_id']:0;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/active/get_attend_list?target_id=%d&event=%d&page=#p#', $this->stash['target_id'], $this->stash['event']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/active/attend_list.html');
  }

	/**
	 * 导出报名列表
	 */
	public function export(){
		$query = array();
		$options = array();
		$page = 1;
		$size = 500;

    $query['target_id'] = (int)$this->stash['target_id'];
    $query['event'] = 1;
		
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
		$head = array('ID', '姓名', '电话', '职业', '地址');
		foreach($head as $i => $v){
			// CSV的Excel支持GBK编码，一定要转换，否则乱码
			// $head[$i] = iconv('utf-8', 'gbk', $v);
		}
		// 将数据通过fputcsv写到文件句柄
		fputcsv($fp, $head);
		
		$service = Sher_Core_Service_Attend::instance();
		
		$is_end = false;
		$counter = 0;
		$limit = 1000;
        $options['size'] = $size;
		$options['sort_field'] = 'latest';
		
		while(!$is_end){
			$options['page'] = $page;
			
			Doggy_Log_Helper::warn("Export active attend-list page[$page],size[$size]!");
			
			$result = $service->get_attend_list($query, $options);
			
			$max = count($result['rows']);
			for($i=0; $i<$max; $i++){
				$counter ++;
				if($limit == $counter){
					ob_flush();
					flush();
					$counter = 0;
				}
				
        $user = $result['rows'][$i]['user'];
        $job = isset($user['profile']['job'])?$user['profile']['job']:'--';
        $address = isset($user['profile']['address'])?$user['profile']['address']:'--';
				
				$row = array($user['_id'], $user['profile']['realname'], $user['profile']['phone'], $job, $address);
				
				/*
				foreach($row as $k => $v){
					// CSV的Excel支持GBK编码，一定要转换，否则乱码
					// $row[$i] = iconv('utf-8', 'gbk', $v);
				}*/
				
				fputcsv($fp, $row);
				
				unset($row);
				unset($user);
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

