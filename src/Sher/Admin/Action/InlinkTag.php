<?php
/**
 * 内链标签管理
 * @author tianshuai
 */
class Sher_Admin_Action_InlinkTag extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
    'size' => 100,
    'kind' => 1,
    'tag'  => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_inlink_tag');
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
    $this->set_target_css_state('all');
		$page = (int)$this->stash['page'];

    $query = array();

    $page = (int)$this->stash['page'];
    $size = (int)$this->stash['size'];

    $kind = (int)$this->stash['kind'];
    $tag = $this->stash['tag'];
    $query['kind'] = $kind;

    if($tag){
      $query['tag'] = $tag;
    }

    $options = array('page'=>$page, 'size'=>$size, 'sort'=>array('created_on'=>1));

		$model = new Sher_Core_Model_InlinkTag();
    $tags = $model->find($query, $options);
    $this->stash['tags'] = $tags;

    $total_count = $this->stash['total_count'] = $model->count($query);
    $this->stash['total_page'] = ceil($total_count/$size);
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/inlink_tag?kind=%d&tag=%s&page=#p#', $kind, $tag);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/inlink_tag/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:'';
		$mode = 'create';
		
		$model = new Sher_Core_Model_InlinkTag();
		if(!empty($id)){
			$mode = 'edit';
			$tag = $model->find_by_id($id);
      $tag = $model->extended_model_row($tag);
      $tag['_id'] = (string)$tag['_id'];
			$this->stash['inlink_tag'] = $tag;

		}
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/inlink_tag/submit.html');
	}

	/**
	 * 创建--批量
	 */
	public function batch_submit(){
		$mode = 'create';
		$this->stash['mode'] = $mode;
		
		return $this->to_html_page('admin/inlink_tag/batch_submit.html');
	}

	/**
	 * 保存信息
	 */
	public function save(){		
		$id = $this->stash['_id'];

		$data = array();
		$data['tag'] = $this->stash['tag'];
		$data['kind'] = (int)$this->stash['kind'];
		$data['links'] = isset($this->stash['links'])?$this->stash['links']:array();
    $data['remark'] = $this->stash['remark'];
		$data['state'] = 1;

		try{
			$model = new Sher_Core_Model_InlinkTag();
			
			if(empty($id)){
				$mode = 'create';
				$data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
				
				$id = (string)$model->id;
			}else{
				$mode = 'edit';
				$data['_id'] = $id;
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("Save inlink tag failed: ".$e->getMessage());
			return $this->ajax_json('保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/inlink_tag';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 批量保存
	 */
	public function batch_save(){		

    $kind = isset($this->stash['kind'])?(int)$this->stash['kind']:1;
    $tags = $this->stash['tags'];
    $user_id = $this->visitor->id;
    if(empty($tags)){
      return $this->ajax_json('请输入标签!', true);  
    }
    $tag_arr = preg_split("/[\s,]+/", $tags);
		$model = new Sher_Core_Model_InlinkTag();
    foreach($tag_arr as $k=>$v){
      $tag = $v;
      $data = array();
      $data['tag'] = $tag;
      $data['kind'] = $kind;
      $data['user_id'] = (int)$user_id;

      try{
        $tag_obj = $model->first(array('kind'=>$kind, 'tag'=>$tag));
        if(empty($tag_obj)){
          $ok = $model->create($data);        
        }     
      }catch(Sher_Core_Model_Exception $e){
        continue;
      }catch(Exception $e){
        echo $e->getMessage();
        continue;
      }

    }
		
		$redirect_url = Doggy_Config::$vars['app.url.admin'].'/inlink_tag';
		
		return $this->ajax_json('保存成功.', false, $redirect_url);
	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('标签不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_InlinkTag();
			
			foreach($ids as $id){
				$tag = $model->load($id);
				
				if (!empty($tag)){
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
