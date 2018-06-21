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
	
	protected $exclude_method_list = array('execute','dream','allist','allist2','dream2','about2','cooperate','rank','ajax_fetch_top_province','ajax_fetch_top_college','ajax_load_colleges','qsyd','qsyd_view','qsyd_list','custom', 'show','qsyd2','qsyd_list2','qsyd_view2','qsyd3','qsyd4','qsyd_view3','qsyd_view4','qsyd_list3','qsyd_list4');
	
    public function _init() {
        //$this->set_target_css_state('page_incubator');
		//$this->set_target_css_state('page_custom');
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->custom();
	}
	
	/**
     * 反向定制
     */
	public function custom(){
		$this->set_target_css_state('page_custom');
		return $this->to_html_page('match/custom.html');
	}
	
    /**
     * 规则（分成奖励）
     */
	public function reward(){
		$this->set_target_css_state('page_custom');
		return $this->to_html_page('match/about3.html');
	}
    
  /**
   * 大赛详情
   */
  public function show(){
	$this->set_target_css_state('page_custom');
    $id = $this->stash['id'];

    $redirect_url = sprintf("%s/about3", Doggy_Config::$vars['app.url.contest']);

		if(empty($id)){
			return $this->show_message_page('缺少请求参数！', $redirect_url);
		}
    
    $model = new Sher_Core_Model_Contest();
    $contest = $model->extend_load((int)$id);

		if(empty($contest)){
			return $this->show_message_page('访问的主题不存在！', $redirect_url);
		}

    if($contest['short_name']=='socket'){
      $this->stash['page_title_suffix'] = '反向定制第三期命题·插排-太火鸟智能硬件孵化平台';
    }

		// 增加pv++
		$model->increase_counter('view_count', 1, $contest['_id']);
        
    $this->stash['target_id'] = $contest['_id'];
    $this->stash['contest'] = $contest;

    $render = sprintf("match/%s.html", $contest['short_name']);
        
    return $this->to_html_page($render);
  }
	
	/**
	 * 奇思甬动-大赛 2
	 */
	public function qsyd(){
		$this->set_target_css_state('page_incubator');
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.qsyd_category_id'];
		
		return $this->to_html_page('match/qsyd.html');
	}

	/**
	 * 奇思甬动-大赛 2
	 */
	public function submit2(){
		$this->set_target_css_state('page_incubator');

    $reason = isset($this->stash['season'])?$this->stash['season']:'';
    $top_category_id = Doggy_Config::$vars['app.contest.qsyd2_category_id'];
    $cate_url = Doggy_Config::$vars['app.url.contest'].'/qsyd';

		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		$parent_category['view_url'] = $cate_url;
		
		$this->stash['parent_category'] = $parent_category;
		$this->stash['mode'] = 'create';
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$this->stash['new_file_id'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		$this->_editor_params();

		return $this->to_html_page('match/qsyd_submit2.html');
	}

	/**
	 * 奇思甬动-大赛 2
	 */
	public function qsyd_view2(){
		$this->set_target_css_state('page_incubator');

		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		
		$redirect_url = Doggy_Config::$vars['app.url.contest']."/qsyd2";
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
		$model->inc_counter('true_view_count', 1, $id);
		$model->inc_counter('web_view_count', 1, $id);
		
		// 当前用户是否有管理权限
		$editable = false;
		if ($this->visitor->id){
			if ($this->visitor->id == $stuff['user_id'] || $this->visitor->can_edit){
				$editable = true;
			}
		}
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		$this->stash['stuff'] = $stuff;

		return $this->to_html_page('match/qsyd_view2.html');
	}

	/**
	 * 奇思甬动-大赛 2
	 */
	public function qsyd_list2(){
    $category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$this->set_target_css_state('page_incubator');
    $top_category_id = Doggy_Config::$vars['app.contest.qsyd2_category_id'];
    $cate_url = Doggy_Config::$vars['app.url.contest'].'/qsyd';

    if(empty($category_id)){
        $this->stash['is_prize'] = 1;
        $this->stash['sort'] = 8;
    }

		$this->stash['cid'] = $top_category_id;
    $this->stash['category_id'] = $category_id;
		$pager_url = sprintf('%s/qsyd_list2?category_id=%d&page=#p#', Doggy_Config::$vars['app.url.contest'], $category_id);
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('match/qsyd_list2.html');
	}

	/**
	 * 奇思甬动-大赛
	 */
	public function qsyd2(){
		$this->set_target_css_state('page_incubator');
    $top_category_id = Doggy_Config::$vars['app.contest.qsyd2_category_id'];
    $cate_url = Doggy_Config::$vars['app.url.contest'].'/qsyd';

		$this->stash['cid'] = $top_category_id;
		return $this->to_html_page('match/qsyd2.html');
	}
	
	/**
	 * 十万火计
	 */
	public function dream(){
		$this->set_target_css_state('page_incubator');
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];

		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);

		//添加网站meta标签
		$this->stash['page_title_suffix'] = "十万火计创意征集大赛-校园季-太火鸟智能硬件孵化平台";
		$this->stash['page_keywords_suffix'] = '太火鸟,智能硬件,智能硬件孵化平台,十万火计,创意征集,校园英雄帖,设计学院,设计专业,产品设计创意,联合孵化实验室,校园人气争霸';   
		$this->stash['page_description_suffix'] = '十万火计创意征集大赛是太火鸟的特色创意征集活动，目前活动举办高校已经达到30余所，随时关注太火鸟活动动态，一起发散思维缔造性感产品。';
		
		return $this->to_html_page('page/match.html');
	}

	/**
	 * 十万火计--第二季
	 */
	public function dream2(){
		$this->set_target_css_state('page_incubator');
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];

		$this->stash['start_time'] = mktime(0,0,0,2,8,2015);
		$this->stash['end_time'] = mktime(23,59,59,4,30,2015);

    //添加网站meta标签
    $this->stash['page_title_suffix'] = "十万火计创意征集大赛-校园季-太火鸟智能硬件孵化平台";
    $this->stash['page_keywords_suffix'] = '太火鸟,智能硬件,智能硬件孵化平台,十万火计,创意征集,校园英雄帖,设计学院,设计专业,产品设计创意,联合孵化实验室,校园人气争霸';   
    $this->stash['page_description_suffix'] = '十万火计创意征集大赛是太火鸟的特色创意征集活动，目前活动举办高校已经达到30余所，随时关注太火鸟活动动态，一起发散思维缔造性感产品。';
		
		return $this->to_html_page('match/match2.html');
	}
	
	/**
	 * 十万火计--第二季 合作资源
	 */
    public function cooperate() {
		$this->set_target_css_state('page_incubator');
		$this->set_target_css_state('cooperate');
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];

        // 添加网站meta标签
        $this->stash['page_title_suffix'] = "十万火计创意征集大赛-校园季-太火鸟智能硬件孵化平台";
        $this->stash['page_keywords_suffix'] = '太火鸟,智能硬件,智能硬件孵化平台,十万火计,创意征集,校园英雄帖,设计学院,设计专业,产品设计创意,联合孵化实验室,校园人气争霸';   
        $this->stash['page_description_suffix'] = '十万火计创意征集大赛是太火鸟的特色创意征集活动，目前活动举办高校已经达到30余所，随时关注太火鸟活动动态，一起发散思维缔造性感产品。';

		return $this->to_html_page('match/cooperate.html');
    }
	
	/**
	 * 十万火计--第二季 作品排行
	 */
    public function rank() {
		$this->set_target_css_state('page_incubator');
		$this->set_target_css_state('active2');
        $this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];

        // 添加网站meta标签
        $this->stash['page_title_suffix'] = "十万火计创意征集大赛-校园季-太火鸟智能硬件孵化平台";
        $this->stash['page_keywords_suffix'] = '太火鸟,智能硬件,智能硬件孵化平台,十万火计,创意征集,校园英雄帖,设计学院,设计专业,产品设计创意,联合孵化实验室,校园人气争霸';   
        $this->stash['page_description_suffix'] = '十万火计创意征集大赛是太火鸟的特色创意征集活动，目前活动举办高校已经达到30余所，随时关注太火鸟活动动态，一起发散思维缔造性感产品。';

		return $this->to_html_page('match/rank.html');
	}
	
	/**
	 * 十万火计--第二季 活动介绍
	 */
    public function about2() {
		$this->set_target_css_state('page_incubator');
		$this->set_target_css_state('about');
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];

        // 添加网站meta标签
        $this->stash['page_title_suffix'] = "十万火计创意征集大赛-校园季-太火鸟智能硬件孵化平台";
        $this->stash['page_keywords_suffix'] = '太火鸟,智能硬件,智能硬件孵化平台,十万火计,创意征集,校园英雄帖,设计学院,设计专业,产品设计创意,联合孵化实验室,校园人气争霸';   
        $this->stash['page_description_suffix'] = '十万火计创意征集大赛是太火鸟的特色创意征集活动，目前活动举办高校已经达到30余所，随时关注太火鸟活动动态，一起发散思维缔造性感产品。';

		return $this->to_html_page('match/about2.html');
	}
	
	/**
	 * 十万火计 全部创意列表
	 */
	public function allist(){
		$this->set_target_css_state('page_incubator');
		$this->set_target_css_state('allist');
		$sort = $this->stash['st'];
		
		$page = "?st=${sort}&page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.contest', 'allist').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);

        // 添加网站meta标签
        $this->stash['page_title_suffix'] = "十万火计创意征集大赛-校园季-太火鸟智能硬件孵化平台";
        $this->stash['page_keywords_suffix'] = '太火鸟,智能硬件,智能硬件孵化平台,十万火计,创意征集,校园英雄帖,设计学院,设计专业,产品设计创意,联合孵化实验室,校园人气争霸';   
        $this->stash['page_description_suffix'] = '十万火计创意征集大赛是太火鸟的特色创意征集活动，目前活动举办高校已经达到30余所，随时关注太火鸟活动动态，一起发散思维缔造性感产品。';
		
		return $this->to_html_page('match/list.html');
	}
	
	/**
	 * 十万火计--第二季 列表页
	 */
    public function allist2() {
		$this->set_target_css_state('page_incubator');
		$this->set_target_css_state('allist');
        $sort = $this->stash['st'];
        $this->stash['pid'] = (int)$this->stash['pid'];
        $this->stash['cid'] = (int)$this->stash['cid'];
		
		$page = "?st=${sort}&cid=".$this->stash['cid']."&page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.contest', 'allist2').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,2,8,2015);
		$this->stash['end_time'] = mktime(23,59,59,4,30,2015);

        // 添加网站meta标签
        $this->stash['page_title_suffix'] = "十万火计创意征集大赛-校园季-太火鸟智能硬件孵化平台";
        $this->stash['page_keywords_suffix'] = '太火鸟,智能硬件,智能硬件孵化平台,十万火计,创意征集,校园英雄帖,设计学院,设计专业,产品设计创意,联合孵化实验室,校园人气争霸';   
        $this->stash['page_description_suffix'] = '十万火计创意征集大赛是太火鸟的特色创意征集活动，目前活动举办高校已经达到30余所，随时关注太火鸟活动动态，一起发散思维缔造性感产品。';

		return $this->to_html_page('match/list2.html');
	}

	/**
	 * 奇思甬动-大赛-- 列表页
	 */
    public function qsyd_list() {
		$this->set_target_css_state('page_incubator');
		$this->set_target_css_state('allist');
        $sort = $this->stash['st'];
        $this->stash['pid'] = (int)$this->stash['pid'];
        $this->stash['cid'] = (int)$this->stash['cid'];
		
		$page = "?st=${sort}&cid=".$this->stash['cid']."&page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.contest', 'qsyd_list').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.qsyd_category_id'];

		return $this->to_html_page('match/qsyd_list.html');
	}

	/**
	 * 大赛提交入口
	 */
	public function submit(){
        $reason = isset($this->stash['season'])?$this->stash['season']:'';
        if($reason=='qsyd'){
            $top_category_id = Doggy_Config::$vars['app.contest.qsyd_category_id'];
            $cate_url = Doggy_Config::$vars['app.url.contest'].'/qsyd';
        }else{
            $top_category_id = Doggy_Config::$vars['app.contest.dream2_category_id'];
            $cate_url = Doggy_Config::$vars['app.url.contest'];
        }

		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		$parent_category['view_url'] = $cate_url;
		
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
		
        if($reason=='qsyd'){
            return $this->to_html_page('match/qsyd_submit.html');   
        }else{
            return $this->to_html_page('match/submit.html');   
        }
	}

	/**
	 * 详情-十万火计2
	 */
	public function view2(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.contest'];
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

        // 添加网站meta标签
        $this->stash['page_title_suffix'] = sprintf("%s-十万火计创意征集大赛-太火鸟智能硬件孵化平台", $stuff['title']);
        if(!empty($stuff['tags_s'])){
            $this->stash['page_keywords_suffix'] = $stuff['tags_s'];   
        }
        $this->stash['page_description_suffix'] = "十万火计创意征集大赛是太火鸟的特色创意征集活动，目前活动举办高校已经达到30余所，随时关注太火鸟活动动态，一起发散思维缔造性感产品。";
		
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
            // 是否显示上传图片/链接
            'comment_show_rich' => 1,
        );
        $this->_comment_param($comment_options);
		
		return $this->to_html_page('match/view2.html');
	}

	/**
	 * 详情-奇思甬动
	 */
	public function qsyd_view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.contest'].'/qsyd';
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
            // 是否显示上传图片/链接
            'comment_show_rich' => 1,
        );
        $this->_comment_param($comment_options);
		
		return $this->to_html_page('match/qsyd_view.html');
	}

    /**
     * 统计
     * ajax获取省份前十
     */
    public function ajax_fetch_top_province(){
        $model = new Sher_Core_Model_SumRecord();
        $query['type'] = Sher_Core_Model_SumRecord::TYPE_PRO;
        $options['page'] = 1;
        $options['size'] = 10;
        $options['sort'] = array('match2_love_count'=> -1);
        $data = $model->find($query, $options);
        foreach($data as $key=>$val){
            $pid = (int)$data[$key]['target_id'];
            $data[$key]['name'] = Sher_Core_Helper_View::show_province_name($pid);
        }
        if(!empty($data)){
            return $this->ajax_json('请求成功', 0, false, $data);
        }else{
            return $this->ajax_json('数据为空',1);
        }
    }

    /**
     * 统计
     * ajax获取大学人气前5
     */
    public function ajax_fetch_top_college(){
        $model = new Sher_Core_Model_SumRecord();
        $query['type'] = Sher_Core_Model_SumRecord::TYPE_COLLEGE;
        $options['page'] = 1;
        $options['size'] = 5;
        $options['sort'] = array('match2_love_count'=> -1);
        $data = $model->find($query,$options);
        $result = array();
        $total_love_count = 0;
        foreach($data as $key=>$val){
            $total_love_count += $val['match2_love_count'];
        }
        // 背景色
        $bg_color = array('#2f87d9','green','orange','gray','#2f87d9');
        $college_mode = new Sher_Core_Model_College();
        foreach($data as $key=>$val){
            $data[$key]['percent'] = (int)($val['match2_love_count']/(float)$total_love_count*100);
            $data[$key]['bg_color'] = $bg_color[$key];
            $college = $college_mode->find_by_id((int)$val['target_id']);
            $data[$key]['name'] = $college['name'];
        }
        $this->stash['college_data'] = $data;
        
        return $this->to_taconite_page('ajax/match_college_graph.html');
    }

    /**
     * ajax加载大学列表
     */
    public function ajax_load_colleges(){
        $category_id = $this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
        $page = (int)$this->stash['page'];
        $size = 10;
        // 排行
        $current_num = ($page-1)*$size;

        // 大学人气排行
        $model = new Sher_Core_Model_SumRecord();
        $query['type'] = Sher_Core_Model_SumRecord::TYPE_COLLEGE;
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort'] = array('match2_love_count'=> -1);
        $data = $model->find($query, $options);
        $college_mode = new Sher_Core_Model_College();
        $stuff_mode = new Sher_Core_Model_Stuff();
        foreach($data as $key=>$val){
            $college = $college_mode->find_by_id((int)$val['target_id']);
            $data[$key]['name'] = $college['name'];
            $data[$key]['pid'] = $college['pid'];
            $data[$key]['top_sort'] = $current_num + $key + 1;
            // 相关作品
            $stuffs = $stuff_mode->find(array('from_to'=>1, 'fid'=>$category_id, 'college_id'=>(int)$val['target_id']), array('page'=>1,'size'=>4, 'sort'=>array('love_count'=>-1)));
            $stuffs = $stuff_mode->extend_load_all($stuffs);
            $data[$key]['stuffs'] = $stuffs;
        }

        $this->stash['colleges'] = $data;
        
        return $this->to_taconite_page('match/match_college_list.html');
    }

	/**
	 * 奇思甬动-大赛 3
	 */
	public function qsyd3(){
		$this->set_target_css_state('page_incubator');
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.qsyd_category_id'];
		
		return $this->to_html_page('match/qsyd3.html');
	}

	/**
	 * 奇思甬动-大赛 4
	 */
	public function qsyd4(){
		$this->set_target_css_state('page_topic');
		$this->set_target_css_state('page_social');
		return $this->to_html_page('match/qsyd4.html');
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


	/**
	 * 奇思甬动-大赛 3
	 */
	public function submit3(){
		$this->set_target_css_state('page_incubator');

    $reason = isset($this->stash['season'])?$this->stash['season']:'';
    $top_category_id = Doggy_Config::$vars['app.contest.qsyd2_category_id'];
    $cate_url = Doggy_Config::$vars['app.url.contest'].'/qsyd';

		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		$parent_category['view_url'] = $cate_url;
		
		$this->stash['parent_category'] = $parent_category;
		$this->stash['mode'] = 'create';
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$this->stash['new_file_id'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		$this->_editor_params();

		return $this->to_html_page('match/qsyd_submit3.html');
	}

	/**
	 * 奇思甬动-大赛 4
	 */
	public function submit4(){
		$this->set_target_css_state('page_topic');
		$this->set_target_css_state('page_social');

    $reason = isset($this->stash['season'])?$this->stash['season']:'';
    $top_category_id = Doggy_Config::$vars['app.contest.qsyd4_category_id'];
    $cate_url = Doggy_Config::$vars['app.url.contest'].'/qsyd';

		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		$parent_category['view_url'] = $cate_url;
		
		$this->stash['parent_category'] = $parent_category;
		$this->stash['mode'] = 'create';
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$this->stash['new_file_id'] = Sher_Core_Helper_Util::generate_mongo_id();
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		$this->_editor_params();

		return $this->to_html_page('match/qsyd_submit4.html');
	}

	/**
	 * 奇思甬动-大赛 3
	 */
	public function qsyd_list3(){
    $category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$this->set_target_css_state('page_incubator');
    $top_category_id = Doggy_Config::$vars['app.contest.qsyd2_category_id'];
    $cate_url = Doggy_Config::$vars['app.url.contest'].'/qsyd';

		$this->stash['cid'] = $top_category_id;
    $this->stash['category_id'] = $category_id;
		$pager_url = sprintf('%s/qsyd_list3?category_id=%d&page=#p#', Doggy_Config::$vars['app.url.contest'], $category_id);
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('match/qsyd_list3.html');
	}

	/**
	 * 奇思甬动-大赛 4
	 */
	public function qsyd_list4(){
    $category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$this->set_target_css_state('page_topic');
		$this->set_target_css_state('page_social');
    $top_category_id = Doggy_Config::$vars['app.contest.qsyd4_category_id'];
    $cate_url = Doggy_Config::$vars['app.url.contest'].'/qsyd4';

		$this->stash['cid'] = $top_category_id;
    $this->stash['category_id'] = $category_id;
		$pager_url = sprintf('%s/qsyd_list4?category_id=%d&page=#p#', Doggy_Config::$vars['app.url.contest'], $category_id);
		$this->stash['pager_url'] = $pager_url;
		return $this->to_html_page('match/qsyd_list4.html');
	}

	/**
	 * 奇思甬动-大赛 3
	 */
	public function qsyd_view3(){
		$this->set_target_css_state('page_incubator');

		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		
		$redirect_url = Doggy_Config::$vars['app.url.contest']."/qsyd3";
		if(empty($id)){
			return $this->show_message_page('访问的项目不存在！', $redirect_url);
		}
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$stuff = $model->load($id);
		
		if(empty($stuff) || $stuff['deleted']){
			return $this->show_message_page('访问的项目不存在或被删除！', $redirect_url);
		}
		
		$stuff = $model->extended_model_row($stuff);
		
		// 增加pv++
		$inc_ran = rand(1,6);
		$model->inc_counter('view_count', $inc_ran, $id);
		$model->inc_counter('true_view_count', 1, $id);
		$model->inc_counter('web_view_count', 1, $id);
		
		// 当前用户是否有管理权限
		$editable = false;
		if ($this->visitor->id){
			if ($this->visitor->id == $stuff['user_id'] || $this->visitor->can_edit){
				$editable = true;
			}
		}
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		$this->stash['stuff'] = $stuff;

		return $this->to_html_page('match/qsyd_view3.html');
	}

	/**
	 * 奇思甬动-大赛 4
	 */
	public function qsyd_view4(){
		$this->set_target_css_state('page_topic');
		$this->set_target_css_state('page_social');

		$id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
		
		$redirect_url = Doggy_Config::$vars['app.url.contest']."/qsyd4";
		if(empty($id)){
			return $this->show_message_page('访问的项目不存在！', $redirect_url);
		}
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$stuff = $model->load($id);
		
		if(empty($stuff) || $stuff['deleted']){
			return $this->show_message_page('访问的项目不存在或被删除！', $redirect_url);
		}
		
		$stuff = $model->extended_model_row($stuff);
		
		// 增加pv++
		$inc_ran = rand(1,6);
		$model->inc_counter('view_count', $inc_ran, $id);
		$model->inc_counter('true_view_count', 1, $id);
		$model->inc_counter('web_view_count', 1, $id);
		
		// 当前用户是否有管理权限
		$editable = false;
		if ($this->visitor->id){
			if ($this->visitor->id == $stuff['user_id'] || $this->visitor->can_edit){
				$editable = true;
			}
		}
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		$this->stash['stuff'] = $stuff;

		return $this->to_html_page('match/qsyd_view4.html');
	}

	/**
	 * 编辑大赛3
	 */
	public function edit3(){
		if(empty($this->stash['id'])){
			return $this->show_message_page('缺少请求参数！', true);
		}
		
		$model = new Sher_Core_Model_Stuff();
		$stuff = $model->load((int)$this->stash['id']);
		
    if(empty($stuff)){
        return $this->show_message_page('编辑的产品不存在或被删除！', true);
    }
		// 仅管理员或本人具有删除权限
		if (!$this->visitor->can_edit() && !($stuff['user_id'] == $this->visitor->id)){
			return $this->show_message_page('你没有权限编辑的该主题！', true);
		}
        
		$stuff = $model->extended_model_row($stuff);

    $reason = isset($this->stash['season'])?$this->stash['season']:'';
    $top_category_id = Doggy_Config::$vars['app.contest.qsyd2_category_id'];
    $cate_url = Doggy_Config::$vars['app.url.contest'].'/qsyd';

		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		$parent_category['view_url'] = $cate_url;
		
		$this->stash['parent_category'] = $parent_category;
		
		$this->stash['mode'] = 'edit';
		$this->stash['stuff'] = $stuff;
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$this->stash['new_file_id'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		return $this->to_html_page('match/qsyd_submit3.html');
	}
	
	
}
