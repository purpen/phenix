<?php
/**
 * 蛋年活动专题页面
 * @author purpen
 */
class Sher_App_Action_Birdegg extends Sher_App_Action_Base {
	public $stash = array(
		'page'=>1,
	);
	
	protected $exclude_method_list = array('execute', 'index', 'zlist', 'view');
	
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->index();
	}
	
	/**
	 * 智能硬件蛋年活动
	 */
	public function index(){
		$top_category_id = Doggy_Config::$vars['app.birdegg.category_id'];
		$this->stash['top_category_id'] = $top_category_id;
		
		return $this->to_html_page('page/birdegg/index.html');
	}
	
	/**
	 * 产品列表
	 */
	public function zlist(){
		$cid = isset($this->stash['cid']) ? $this->stash['cid'] : 0;
		$top_category_id = Doggy_Config::$vars['app.birdegg.category_id'];
		$is_top = false;
		if(!$cid || ($cid == $top_category_id)){
			$this->stash['all_stuff'] = 'active';
			$cid = $top_category_id;
			$is_top = true;
		}
		$this->stash['is_top'] = $is_top;
		$this->stash['top_category_id'] = $top_category_id;
		$this->stash['cid'] = $cid;
		
		// 分页链接
		$page = 'p#p#';
		$this->stash['pager_url'] = Sher_Core_Helper_Url::build_url_path('app.url.birdegg', 'c'.$cid).$page;
		
		return $this->to_html_page('page/birdegg/zlist.html');
	}
	
	/**
	 * 产品详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.birdegg'];
		if(empty($id)){
			return $this->show_message_page('访问的产品不存在！', $redirect_url);
		}
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$stuff = $model->load($id);
		
		if(empty($stuff) || $stuff['deleted']){
			return $this->show_message_page('访问的产品不存在或被删除！', $redirect_url);
		}
		
		$stuff = $model->extended_model_row($stuff);
		
		// 增加pv++
		$inc_ran = rand(1,6);
		$model->inc_counter('view_count', $inc_ran, $id);
		
		// 当前用户是否有管理权限
		$editable = false;
		if ($this->visitor->id){
			if ($this->visitor->id == $stuff['user_id'] || $this->visitor->can_admin){
				$editable = true;
			}
		}
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$stuff['fid']);
		
		$this->stash['stuff'] = $stuff;
		$this->stash['parent_category'] = $parent_category;
		$this->stash['editable'] = $editable;
		
    	// 评论参数
    	$this->stash['comment_target_id'] = (int)$stuff['_id'];
    	$this->stash['comment_type'] = Sher_Core_Model_Comment::TYPE_STUFF;
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::stuff_comment_url($id, '#p#');
		
		return $this->to_html_page('page/birdegg/view.html');
	}
	
	/**
	 * 蛋年活动提交入口
	 */
	public function submit(){
		$top_category_id = Doggy_Config::$vars['app.birdegg.category_id'];
		
		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		$parent_category['view_url'] = Doggy_Config::$vars['app.url.birdegg'];
		
		$this->stash['parent_category'] = $parent_category;
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		
		$this->_editor_params();
		
		return $this->to_html_page('page/stuff/submit.html');
	}
	
	/**
	 * 编辑器参数
	 */
	protected function _editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url);
		$new_pic_id = new MongoId();
		$this->stash['editor_pid'] = (string)$new_pic_id;

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF_EDITOR;
	}
	
	
}
?>
