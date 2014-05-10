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
	 * 保存公告
	 */
	public function save() {
		$row = array();
		$row['user_id'] = (int)$this->visitor->id;
		$row['content'] = $this->stash['content'];
		// 验证数据
		if(empty($row['content'])){
			return $this->ajax_note('获取数据错误,请重新提交', true);
		}
		
		$cake = new Sher_Core_Model_Cake();
		if(empty($this->stash['_id'])){
			$ok = $cake->apply_and_save($row);
		}else{
			$row['_id'] = $this->stash['_id'];
			$ok = $cake->apply_and_update($row);
		}
		
		if(!$ok){
			return $this->ajax_note('数据保存失败,请重新提交', true);
		}
		
		return $this->ajax_notification('公告保存成功.');
	}
	
	/**
	 * 删除公告
	 */
	public function delete() {
		$id = $this->stash['id'];
		if(!empty($id)){
			$cake = new Sher_Core_Model_Cake();
			$cake->remove($id);
		}
		$this->stash['id'] = $id;
		return $this->to_taconite_page('admin/del_ok.html');
	}
	
	
}
?>