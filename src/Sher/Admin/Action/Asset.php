<?php
/**
 * 附件管理
 * @author purpen
 */
class Sher_Admin_Action_Asset extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize,Doggy_Dispatcher_Action_Interface_UploadSupport {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
    private $asset = array();
    
    public function setUploadFiles($files){
        $this->asset = $files;
    }
	
	public function _init() {
		$this->set_target_css_state('page_asset');
		// 判断左栏类型
		$this->stash['show_type'] = "assist";
  }
	
	/**
	 * 附件列表
	 */
	public function execute(){
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/asset?page=#p#';
		
		$this->stash['pager_url'] = $pager_url;
		$model = new Sher_Core_Model_Asset();
		$this->stash['thumb_info'] = $model->thumb_info();
		
		return $this->to_html_page('admin/asset/list.html');
	}
	
	/**
	 * 上传附件
	 */
	public function upload() {
		$this->stash['user_id'] = $this->visitor->id;
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['pid'] = new MongoId();
		
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_ASSET;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_ASSET;
		
		return $this->to_html_page('admin/asset/upload.html');
	}
	
	/**
	 * 删除附件
	 */
	public function delete() {
		$id = $this->stash['id'];
		if(!empty($id)){
			$model = new Sher_Core_Model_Asset();
			$model->delete_file($id);
		}
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}

	/**
	 * 删除某个附件
	 */
	public function delete_asset(){
		$id = $this->stash['id'];
    $asset_id = $this->stash['asset_id'];
    $model_name = $this->stash['model'];
		if (empty($asset_id)){
			return $this->ajax_note('附件不存在！', true);
		}
		
		if (!empty($id)){
      if($model_name=='Active'){
			  $model = new Sher_Core_Model_Active();
      }elseif($model_name=='Product'){
 			  $model = new Sher_Core_Model_Product();   
      }

			$model->delete_asset($id, $asset_id);
		}else{
			// 仅仅删除附件
			$asset = new Sher_Core_Model_Asset();
			$asset->delete_file($id);
		}
		
		return $this->to_taconite_page('ajax/delete_asset.html');
	}

  /**
   * 保存图片描述信息
   */
  public function save_asset_desc(){
    if(empty($this->stash['asset_id'])){
 			return $this->ajax_note('参数不完整！', true); 
    }
    $asset = new Sher_Core_Model_Asset();
    $ok = $asset->update_set($this->stash['asset_id'], array('desc'=>$this->stash['desc']));
    if($ok){
 		  return $this->to_taconite_page('ajax/save_asset_desc.html');   
    }else{
  	  return $this->ajax_note('保存失败！', true);   
    }
  }
	
	
}

