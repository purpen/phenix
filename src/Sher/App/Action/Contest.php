<?php
/**
 * 十万火计-大赛
 * @author purpen
 */
class Sher_App_Action_Contest extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'page' => 1,
		'step' => 0,
		'pid'  => 0,
		'cid'  => 0,
		'sort' => 0,
		'st' => 0,
	);
	
	protected $page_tab = 'page_sns';
	protected $page_html = 'page/social/index.html';
	
	protected $exclude_method_list = array('execute','dream','allist','allist2','dream2','about2','cooperate','rank');
	
	public function _init() {
		$this->set_target_css_state('page_social');
  }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->dream2();
	}
	
	/**
	 * 社区首页
	 */
	public function index(){

	}
	
	/**
	 * 十万火计
	 */
	public function dream(){
		$this->set_target_css_state('index');
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];

		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('page/match.html');
	}

	/**
	 * 十万火计--第二季
	 */
	public function dream2(){
		$this->set_target_css_state('index');
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];

		$this->stash['start_time'] = mktime(0,0,0,2,8,2015);
		$this->stash['end_time'] = mktime(23,59,59,4,30,2015);
		
		return $this->to_html_page('match/match2.html');
	}
	
	/**
	 * 十万火计--第二季 合作资源
	 */
  public function cooperate() {
		$this->set_target_css_state('cooperate');
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		return $this->to_html_page('match/cooperate.html');
	}
	
	/**
	 * 十万火计--第二季 作品排行
	 */
  public function rank() {
		$this->set_target_css_state('active2');
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		return $this->to_html_page('match/rank.html');
	}
	
	/**
	 * 十万火计--第二季 活动介绍
	 */
  public function about2() {
		$this->set_target_css_state('about');
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		return $this->to_html_page('match/about2.html');
	}
	
	/**
	 * 十万火计 全部创意列表
	 */
	public function allist(){
		$this->set_target_css_state('allist');
		$sort = $this->stash['st'];
		
		$page = "?st=${sort}&page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.contest', 'allist').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('match/list.html');
	}
	
	/**
	 * 十万火计--第二季 列表页
	 */
  public function allist2() {
		$this->set_target_css_state('allist');
		$sort = $this->stash['st'];
		
		$page = "?st=${sort}&page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.contest', 'allist2').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,2,8,2015);
		$this->stash['end_time'] = mktime(23,59,59,4,30,2015);
		return $this->to_html_page('match/list2.html');
	}

	/**
	 * 十万火计提交入口
	 */
	public function submit(){
		$top_category_id = Doggy_Config::$vars['app.contest.dream2_category_id'];

		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		$parent_category['view_url'] = Doggy_Config::$vars['app.url.contest'];
		
		$this->stash['parent_category'] = $parent_category;
		$this->stash['mode'] = 'create';
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		$this->_editor_params();
		
		return $this->to_html_page('match/submit.html');
	}

	/**
	 * 详情-十万火计2
	 */
	public function view2(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.stuff'];
		if(empty($id)){
			return $this->show_message_page('访问的作品不存在！', $redirect_url);
		}
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$stuff = $model->load($id);
		
		if(empty($stuff) || $stuff['deleted']){
			return $this->show_message_page('访问的作品不存在或被删除！', $redirect_url);
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
    $comment_options = array(
      'comment_target_id' => $stuff['_id'],
      'comment_target_user_id' => $stuff['user_id'],
      'comment_type'  =>  Sher_Core_Model_Comment::TYPE_STUFF,
      'comment_pager' =>  Sher_Core_Helper_Url::stuff_comment_url($id, '#p#'),
      //是否显示上传图片/链接
      'comment_show_rich' => 1,
    );
    $this->_comment_param($comment_options);
		
		return $this->to_html_page('match/view2.html');
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

  /**
   * 评论参数
   */
  protected function _comment_param($options){
    $this->stash['comment_target_id'] = $options['comment_target_id'];
    $this->stash['comment_target_user_id'] = $options['comment_target_user_id'];
    $this->stash['comment_type'] = $options['comment_type'];

		// 评论的链接URL
		$this->stash['pager_url'] = $options['comment_pager'];

        // 是否显示图文并茂
        $this->stash['comment_show_rich'] = $options['comment_show_rich'];
		// 评论图片上传参数
		$this->stash['comment_token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['comment_domain'] = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$this->stash['comment_asset_type'] = Sher_Core_Model_Asset::TYPE_COMMENT;
		$this->stash['comment_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
    }
	
}
?>
