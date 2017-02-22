<?php
/**
 * 蛋年活动专题页面
 * @author purpen
 */
class Sher_Wap_Action_Birdegg extends Sher_Wap_Action_Base {
	public $stash = array(
		'page'=>1,
    'sort'=>0,
    'page_title_suffix' => '中国智能硬件蛋年创新大会-太火鸟智能硬件孵化平台',
    'page_keywords_suffix' => '太火鸟,智能硬件,智能硬件孵化平台,蛋年,蛋年创新大会,硬件圈,媒体圈,投资圈,平台圈,机构圈,媒体圈',
    'page_description_suffix' => '中国智能硬件.蛋年创新大会是中国首个集合硬件、媒体、投资、平台、机构等多阵营的业内创新交流大会，蛋年创新大会与您聚焦中国智能硬件的发展趋势，畅聊智能硬件发展未来。',
	);
	
	protected $exclude_method_list = array('execute', 'index', 'zlist', 'view', 'sz','test');
	
	/**
	 * 默认入口
	 */
	public function execute(){
		return $this->index();
	}
	
	
	public function test(){
		/*$this->stash['page_title_suffix'] = '金投赏巅峰对话：从广告营销看工业设计的跨界融合';
		//微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);*/
		return $this->to_html_page('wap/birdegg/test.html');
	}
	/**
	 * 智能硬件蛋年活动
	 */
	public function index(){
		$cid = isset($this->stash['cid']) ? $this->stash['cid'] : 0;
		$top_category_id = Doggy_Config::$vars['app.birdegg.category_id'];
		$is_top = false;
		$this->stash['is_top'] = $is_top;
		$this->stash['top_category_id'] = $top_category_id;
		$this->stash['cid'] = $cid;

    //作品推荐列表---取块内容
    $stuff_ids = Sher_Core_Util_View::load_block('birdegg_index_stick', 1);
    $stuffs = array();
    if($stuff_ids){
      $stuff_model = new Sher_Core_Model_Stuff();
      $id_arr = explode(',', $stuff_ids);
      foreach(array_slice($id_arr, 0, 8) as $i){
        $stuff = $stuff_model->extend_load((int)$i);
        if(!empty($stuff)){
          array_push($stuffs, $stuff);
        }
      }
    }
    $this->stash['stuffs'] = $stuffs;
		
		return $this->to_html_page('wap/birdegg/index.html');
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
		$this->stash['pager_url'] = Sher_Core_Helper_Url::build_url_path('app.url.wap.birdegg', 'c'.$cid).$page;
		
		return $this->to_html_page('wap/birdegg/list.html');
	}
	
	/**
	 * 产品详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.wap'].'/birdegg';
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

    //添加网站meta标签
    $this->stash['page_title_suffix'] = sprintf("%s-中国智能硬件蛋年创新大会-太火鸟智能硬件孵化平台", $stuff['title']);
    if(!empty($stuff['tags_s'])){
      $this->stash['page_keywords_suffix'] = $stuff['tags_s'];   
    }
    $this->stash['page_description_suffix'] = sprintf("中国智能硬件.蛋年创新大会是中国首个集合硬件、媒体、投资、平台、机构等多阵营的业内创新交流大会，蛋年创新大会与您聚焦中国智能硬件的发展趋势，畅聊智能硬件发展未来。", '');
		
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
    	$this->stash['comment_target_id'] = $stuff['_id'];
    	$this->stash['comment_target_user_id'] = $stuff['user_id'];
    	$this->stash['comment_type'] = Sher_Core_Model_Comment::TYPE_STUFF;
		$comment_alert = '写下你的想法，就有机会被封存到时光胶囊中，接受时间的洗礼！';
	    $this->stash['comment_alert'] = $comment_alert;
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::stuff_comment_url($id, '#p#');
		
		return $this->to_html_page('wap/birdegg/show.html');
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
    $this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		return $this->to_html_page('wap/birdegg/submit.html');
	}

  /**
   * 深圳
   */
  public function sz(){
    //微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
    return $this->to_html_page('wap/birdegg/sz.html');
  }

  /**
   * 深圳蛋年报名入口
   */
  public function sz_apply(){

    $this->stash['area_options'] = Sher_Core_Util_Constant::birdegg_area_options();
    $this->stash['interest_options'] = Sher_Core_Util_Constant::birdegg_interest_options();

 		return $this->to_html_page('wap/birdegg/sz_apply.html'); 
  }

  /**
   * 分享窗口
   */
  public function sz_share(){
    //微信分享
    $this->stash['app_id'] = Doggy_Config::$vars['app.wechat.app_id'];
    $timestamp = $this->stash['timestamp'] = time();
    $wxnonceStr = $this->stash['wxnonceStr'] = new MongoId();
    $wxticket = Sher_Core_Util_WechatJs::wx_get_jsapi_ticket();
    $url = $this->stash['current_url'] = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']; 
    $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $wxticket, $wxnonceStr, $timestamp, $url);
    $this->stash['wxSha1'] = sha1($wxOri);
 		return $this->to_html_page('wap/birdegg/sz_share.html'); 
  }
	
}
?>
