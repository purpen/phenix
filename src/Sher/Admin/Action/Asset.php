<?php
/**
 * 附件管理
 * @author purpen
 */
class Sher_Admin_Action_Asset extends Sher_Admin_Action_Base implements Doggy_Dispatcher_Action_Interface_UploadSupport {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
    private $asset = array();
    
    //interface implements
    public function setUploadFiles($files){
        $this->asset = $files;
    }
	
	/**
	 * 附件列表
	 */
	public function execute(){
		$this->set_target_css_state('page_asset');
		
		$pager_url = Doggy_Config::$vars['app.url.admin'].'/asset?page=#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
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
	
	
}
?>