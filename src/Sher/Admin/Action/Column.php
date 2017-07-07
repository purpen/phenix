<?php
/**
 * 栏目管理
 * @author tianshuai
 */
class Sher_Admin_Action_Column extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_column');
		$this->stash['show_type'] = "common";
  }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $this->set_target_css_state('all');
		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/column?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/column/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:'';
		$mode = 'create';
		
		$model = new Sher_Core_Model_Column();
		if(!empty($id)){
			$mode = 'edit';
			$column = $model->find_by_id($id);
		$column = $model->extended_model_row($column);
		$column['_id'] = (string)$column['_id'];

    if(is_array($column['item'])){
      foreach($column['item'] as $key=>$d){
        $join_item = $d['sort'].'@@'.$d['title'].'@@'.$d['sub_title'].'@@'.$d['url'].'@@'.$d['cover_url'].'@@'.$d['target_id'].'@@'.$d['begin_time'].'@@'.$d['end_time'].'@@'.$d['sale_price'].'@@'.$d['market_price'].'@@'.$d['total'];
        $column['item'][$key]['join_item'] = $join_item;         
      }
    }

			$this->stash['column'] = $column;

		}
		$this->stash['mode'] = $mode;
		
		// 编辑器上传附件
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$this->stash['editor_pid'] = new MongoId();

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_BLOCK;
		
		return $this->to_html_page('admin/column/submit.html');
	}

	/**
	 * 保存信息
	 */
	public function save(){		
    $id = $this->stash['_id'];
    $user_id = (int)$this->visitor->id;

		$data = array();
		$data['mark'] = $this->stash['mark'];
		$data['name'] = $this->stash['name'];
		$data['remark'] = $this->stash['remark'];
    $data['type'] = isset($this->stash['type']) ? (int)$this->stash['type'] : 1;
    $data['state'] = 1;

    //进度安排
    $data['item'] = array();
    if(isset($this->stash['item'])){
      $p_arr = array();
      foreach($this->stash['item'] as $d){
        $s_arr = array();
        $arr_item = explode('@@', $d);
        $s_arr['sort'] = $arr_item[0];
        $s_arr['title'] = $arr_item[1];
        $s_arr['sub_title'] = $arr_item[2];
        $s_arr['url'] = $arr_item[3];
        $s_arr['cover_url'] = $arr_item[4];
        $s_arr['target_id'] = $arr_item[5];
        $s_arr['begin_time'] = $arr_item[6];
        $s_arr['end_time'] = $arr_item[7];
        $s_arr['sale_price'] = $arr_item[8];
        $s_arr['market_price'] = $arr_item[9];
        $s_arr['total'] = $arr_item[10];
        array_push($p_arr, $s_arr);
      }
      $data['item'] = $p_arr;
    }

		try{
			$model = new Sher_Core_Model_Column();
			
			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = $user_id;
				$ok = $model->apply_and_save($data);
				
				$id = (string)$model->id;
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
        $column = $model->load($id);
        if(empty($column)){
          return $this->ajax_json('内容不存在!', true);
        }
  
        // 是否允许编辑操作
        $mark_arr = Sher_Core_Model_Column::mark_safer();
        if(in_array($column['mark'], $mark_arr)){
          if(!Sher_Core_Helper_Util::is_high_admin($user_id)){
            return $this->ajax_json('没有执行权限!', true);     
          }
        }
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			// 上传成功后，更新所属的附件
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save column failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/column';
		
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
		$id = $this->stash['id'];
    $user_id = $this->visitor->id;
		if(empty($id)){
			return $this->ajax_notification('块不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
      if(!Sher_Core_Helper_Util::is_high_admin($user_id)){
        return $this->ajax_notification('没有执行权限!', true);     
      }
			$model = new Sher_Core_Model_Column();
			
			foreach($ids as $id){
				$column = $model->load($id);
				
				if (!empty($column)){

					$model->remove($id);
					// 删除关联对象
					$model->mock_after_remove($id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

}

