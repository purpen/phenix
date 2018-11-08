<?php
/**
 * 社区帖子
 * @author purpen
 * @author caowei@taohuoniao.com
 */
class Sher_App_Action_Topic extends Sher_App_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'id' => '',
		'cover_id' => 0,
		'category_id' => 0,
        'page' => 1,
        'size' => 8,
		'type' => 0,
		'time' => 0,
		'page' => 1,
		'cid'  => 0,
        'floor'=> 0,
		'ref'  => null,
		'page_title_suffix' => '太火鸟智能硬件社区',
		'page_keywords_suffix' => '智能硬件社区,孵化需求,活动动态,品牌专区,产品评测,太火鸟,智能硬件,智能硬件孵化,孵化社区,创意众筹,硬件营销,硬件推广',
		'page_description_suffix' => '太火鸟智能硬件社区为广大智能硬件爱好者提供智能硬件讨论、智能硬件创意提交、智能硬件活动体验，社区包括智创学堂，孵化需求，活动动态，品牌专区，产品评测等几大社区板块以及10000+智能硬件话题。',
	);
	
	protected $page_tab = 'page_topic';
	protected $page_html = 'page/topic/index.html';
	protected $exclude_method_list = array('execute', 'index', 'ajax_fetch_more', 'get_list', 'view', 'ajax_guess_topics');
	
	public function _init() {
		$this->set_target_css_state('page_social');
		$this->set_target_css_state('page_topic');
		$this->set_target_css_state('page_sub_topic');
        
		$this->stash['domain'] = Sher_Core_Util_Constant::TYPE_TOPIC;
        
        // 获取登陆者信息
        $this->stash['user'] = array();
        if($this->visitor->id){
            $user = new Sher_Core_Model_User();
            $row = $user->load((int)$this->visitor->id);
            if(!empty($row)){
                $this->stash['user'] = $user->extended_model_row($row);
            }
            // 用户实时积分
            $point_model = new Sher_Core_Model_UserPointBalance();
            $current_point = $point_model->load((int)$this->visitor->id);
            $this->stash['current_point'] = $current_point;
        }
    }
	
	/**
	 * 社区
	 */
	public function execute(){
		return $this->index();
		# return $this->index2();
	}

	/**
     * 社区资讯
     */
    	public function message(){
    		return $this->to_html_page('page/topic/message.html');
    	}
	
	/**
	 * 社区首页
	 */
	public function index(){
        
		$category_id = $this->stash['category_id'];
		$type = $this->stash['type'];
        $time = $this->stash['time'];
		$sort = $this->stash['sort'] = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 1;
        $page = $this->stash['page'];

		// 综合分类
		$this->stash['topic_category_official'] = Doggy_Config::$vars['app.topic_category_official'];
		// 产品分类
		$this->stash['topic_category_user'] = Doggy_Config::$vars['app.topic_category_user'];
        
		// 获取置顶列表
		$diglist = array();
		$dig_ids = array();
        
		$digged = new Sher_Core_Model_DigList();
		$result = $digged->load(Sher_Core_Util_Constant::DIG_TOPIC_TOP);
		if (!empty($result) && !empty($result['items'])) {
			$model = new Sher_Core_Model_Topic();
			$diglist = $model->extend_load_all($result['items']);
			
	        for ($i=0; $i < count($result['items']); $i++) {
				$dig_ids[] = is_array($result['items'][$i]) ? $result['items'][$i]['_id'] : $result['items'][$i];
	        }
		}

        // 昨天的日期
        $yesterday = (int)date('Ymd' , strtotime('-1 day'));
        $this->stash['yesterday'] = $yesterday;
        
		$this->stash['dig_ids']  = $dig_ids;
		$this->stash['dig_list'] = $diglist;
        
        $this->gen_advanced_links($category_id, $type, $time, $sort, $page);
        
		return $this->to_html_page('page/topic/index.html');
	}

	/**
	 * 社区首页测试
	 */
	public function index2(){

		$category_id = $this->stash['category_id'];
		$type = $this->stash['type'];
        $time = $this->stash['time'];
		$sort = $this->stash['sort'] = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 1;
        $page = $this->stash['page'];

		// 综合分类
		$this->stash['topic_category_official'] = Doggy_Config::$vars['app.topic_category_official'];
		// 产品分类
		$this->stash['topic_category_user'] = Doggy_Config::$vars['app.topic_category_user'];

		// 获取置顶列表
		$diglist = array();
		$dig_ids = array();

		$digged = new Sher_Core_Model_DigList();
		$result = $digged->load(Sher_Core_Util_Constant::DIG_TOPIC_TOP);
		if (!empty($result) && !empty($result['items'])) {
			$model = new Sher_Core_Model_Topic();
			$diglist = $model->extend_load_all($result['items']);

	        for ($i=0; $i < count($result['items']); $i++) {
				$dig_ids[] = is_array($result['items'][$i]) ? $result['items'][$i]['_id'] : $result['items'][$i];
	        }
		}

        // 昨天的日期
        $yesterday = (int)date('Ymd' , strtotime('-1 day'));
        $this->stash['yesterday'] = $yesterday;

		$this->stash['dig_ids']  = $dig_ids;
		$this->stash['dig_list'] = $diglist;

        $this->gen_advanced_links($category_id, $type, $time, $sort, $page);

		return $this->to_html_page('page/topic/index2.html');
	}
    
    /**
     * 自动加载获取
     */
    public function ajax_fetch_more(){
        $category_id = (int)$this->stash['category_id'];
		$type = (int)$this->stash['type'];
        $time = (int)$this->stash['time'];
		$sort = $this->stash['sort'] = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
        $page = (int)$this->stash['page'];
        $size = (int)$this->stash['size'];
        
		$page = $this->stash['page'];
        $service = Sher_Core_Service_Topic::instance();
        
        $query = array();
        
		if($category_id){
			$query['category_id'] = (int)$category_id;
		}
        
        $query['published'] = 1;
        $query['verifyed'] = 1;
        $query['deleted'] = 0;
        
		// 类别
		if($type == 1){
			// 推荐
			$query['stick'] = 1;
		}elseif ($type == 2){
			$query['fine']  = 1;
    }elseif($type == 4){  // 活动 
      $query['attrbute'] = Sher_Core_Model_Topic::ATTR_ACTIVE;   
		}else{
			//为0
		}
        
		// 时间
		$day = 24 * 60 * 60;
		switch ($time) {
			case 0:
				break;
			case 1:
				$query['created_on'] = array('$gte'=> time() - $day);
				break;
			case 2:
				$query['created_on'] = array('$gte'=> time() - 7*$day);
				break;
			case 3:
				$query['created_on'] = array('$gte'=> time() - 30*$day);
				break;
			case 4:
				$query['created_on'] = array('$gte'=> time() - 90*$day);
				break;
		}
        
        $options['page'] = $page;
        $options['size'] = $size;
        
		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'latest';
				break;
			case 2:
				$options['sort_field'] = 'comment';
				break;
			case 5:
				$options['sort_field'] = 'view';
				break;
            case 7:
                $options['sort_field'] = 'last_reply';
                break;
		}

        // 限制输出字段
        $some_fields = array(
          '_id'=>1, 'title'=>1, 'short_title'=>1, 'user_id'=>1, 't_color'=>1, 'top'=>1,
          'fine'=>1, 'stick'=>1, 'category_id'=>1, 'created_on'=>1, 'asset_count'=>1,
          'last_user'=>1, 'last_reply_time'=>1, 'cover_id'=>1, 'comment_count'=>1, 'view_count'=>1,
          'updated_on'=>1, 'favorite_count'=>1, 'love_count'=>1, 'deleted'=>1,'published'=>1, 'tags'=>1,
          'description'=>1, 'attrbute'=>1,
        );
        $options['some_fields'] = $some_fields;
        
        $resultlist = $service->get_topic_list($query,$options);
        $next_page = 'no';
        if(isset($resultlist['next_page'])){
            if((int)$resultlist['next_page'] > $page){
                $next_page = (int)$resultlist['next_page'];
            }
        }
        
        $max = count($resultlist['rows']);
        for($i=0;$i<$max;$i++){
            $symbol = isset($resultlist['rows'][$i]['user']['symbol']) ? $resultlist['rows'][$i]['user']['symbol'] : 0;
            if(!empty($symbol)){
              $s_key = sprintf("symbol_%d", $symbol);
              $resultlist['rows'][$i]['user'][$s_key] = true;
            }
            /*if($resultlist['rows'][$i]['asset_count'] > 0){
                $resultlist['rows'][$i]['has_asset'] = true;
                $asset = Sher_Core_Service_Asset::instance();
                $q = array(
                    'parent_id'  => $resultlist['rows'][$i]['_id'],
                    'asset_type' => 55,
                );
                $op = array(
                    'page' => 1,
                    'size' => !empty($resultlist['rows'][$i]['cover'])?4:5,
                    'sort_field' => 'positive',
                );
                $asset_result = $asset->get_asset_list($q, $op);
                $resultlist['rows'][$i]['asset_list'] = $asset_result['rows'];
                
                //print_r($resultlist['rows'][$i]['asset_list']);
            }else{
                $resultlist['rows'][$i]['has_asset'] = false;
            }*/

            // 过滤用户表
            if(isset($resultlist['rows'][$i]['user'])){
              $resultlist['rows'][$i]['user'] = Sher_Core_Helper_FilterFields::user_list($resultlist['rows'][$i]['user'], array('symbol_1', 'symbol_2'));
            }
            if(isset($resultlist['rows'][$i]['last_user'])){
              $resultlist['rows'][$i]['last_user'] = Sher_Core_Helper_FilterFields::user_list($resultlist['rows'][$i]['last_user'], array('symbol_1', 'symbol_2'));
            }
        } //end for

        $data = array();
        $data['nex_page'] = $next_page;
        $data['results'] = $resultlist;
        
        return $this->ajax_json('', false, '', $data);
    }
    
	/**
	 * 获取相关话题
	 */
	public function ajax_guess_topics(){
		$sword = $this->stash['sword'];
    $current_id = $this->stash['id'];
		$size = $this->stash['size'];
        
		$result = array();
		$options = array(
			'page' => 1,
			'size' => $size,
			'sort_field' => 'latest',
      // 最新排序
      'sort' => 1,
			'evt' => 'tag',
			't' => 2,
			'oid' => $current_id,
			'type' => 1,
		);
        
		if(!empty($sword)){
      $xun_arr = Sher_Core_Util_XunSearch::search($sword, $options);
      if($xun_arr['success'] && !empty($xun_arr['data'])){
        $topic_mode = new Sher_Core_Model_Topic();
        $items = array();
        foreach($xun_arr['data'] as $k=>$v){
          // 过滤当前对象
          if((int)$current_id == (int)$v['oid']){
            continue;
          }
          $topic = $topic_mode->extend_load((int)$v['oid']);
          if(!empty($topic)){
            // 过滤用户表
            if(isset($topic['user'])){
              $topic['user'] = Sher_Core_Helper_FilterFields::user_list($topic['user']);
            }
            if(isset($topic['last_user'])){
              $topic['last_user'] = Sher_Core_Helper_FilterFields::user_list($topic['last_user']);
            }
            array_push($items, array('topic'=>$topic));
          }
        }
        $result['rows'] = $items;
        $result['total_rows'] = $xun_arr['total_count'];
      }else{
        $addition_criteria = array(
            'type' => 2,
            'target_id' => array('$ne' => (int)$current_id),
        );
        $sword = array_values(array_unique(preg_split('/[,，\s]+/u', $sword)));
        //$result = Sher_Core_Service_Search::instance()->search(implode('',$sword), 'full', $addition_criteria, $options);     
        $result = array();
      }

		}
    if(!empty($result)){
      $data['state'] = 1;
      $data['result'] = $result;
    }else{
      $data['state'] = 0;
      $data['result'] = '';   
    }
    return $this->ajax_json('', false, '', $data);
	}
	
	/**
	 * 社区列表
	 */
	public function get_list(){
		
		// 获取置顶列表
		$diglist = array();
		$dig_ids = array();
		$current_category = array();
		$parent_category = array();

		// 综合分类
		$this->stash['topic_category_official'] = Doggy_Config::$vars['app.topic_category_official'];
		// 产品分类
		$this->stash['topic_category_user'] = Doggy_Config::$vars['app.topic_category_user'];
		
		$digged = new Sher_Core_Model_DigList();
		$result = $digged->load(Sher_Core_Util_Constant::DIG_TOPIC_TOP);
		if (!empty($result) && !empty($result['items'])) {
			$model = new Sher_Core_Model_Topic();
			$diglist = $model->extend_load_all($result['items']);
			
	        for ($i=0; $i < count($result['items']); $i++) {
				$dig_ids[] = is_array($result['items'][$i]) ? $result['items'][$i]['_id'] : $result['items'][$i];
	        }
		}
        
		// 获取列表
		$category_id = $this->stash['category_id'];
		
		$type = $this->stash['type'];
		$time = $this->stash['time'];
		$sort = $this->stash['sort'] = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
		$page = $this->stash['page'];
        
        // 获取版块置顶列表
		$dig_cate_list = array();
		$dig_cate_ids = array();
        if(!empty($category_id)){
            $key_id = Sher_Core_Util_Constant::top_topic_category_key($category_id);
            $category_result = $digged->load($key_id);
    		if (!empty($category_result) && !empty($category_result['items'])) {
    			$model = new Sher_Core_Model_Topic();
    			$dig_cate_list = $model->extend_load_all($category_result['items']);
			
    	        for ($i=0; $i<count($category_result['items']); $i++) {
    				$dig_cate_ids[] = is_array($category_result['items'][$i]) ? $category_result['items'][$i]['_id'] : $category_result['items'][$i];
    	        }
    		}
        }
		
		$pager_url = Sher_Core_Helper_Url::topic_list_url($category_id, $type, $time, $sort).'p#p#';
		
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dig_ids']  = $dig_ids;
		$this->stash['dig_list'] = $diglist;
        
		$this->stash['dig_cate_ids']  = $dig_cate_ids;
		$this->stash['dig_cate_list'] = $dig_cate_list;
		
		$this->gen_advanced_links($category_id, $type, $time, $sort, $page);
		
		// 是否为一级分类
		$is_top = true;
		// 获取当前分类信息
		if($category_id){
            // 根据分类ID,显示描述信息
            $this->stash['category_desc'] = Sher_Core_Helper_View::category_desc_show($category_id);
			$category = new Sher_Core_Model_Category();
			$current_category = $category->extend_load((int)$category_id);
			// 存在父级分类，标识是二级分类
			if (!empty($current_category['pid'])){
				$is_top = false;
				// 获取父级分类
				$parent_category = $category->extend_load((int)$current_category['pid']);
			}

            // 添加网站meta标签
            $this->stash['page_title_suffix'] = Sher_Core_Helper_View::meta_category_obj($current_category, 1);
            $this->stash['page_keywords_suffix'] = Sher_Core_Helper_View::meta_category_obj($current_category, 2);   
            $this->stash['page_description_suffix'] = Sher_Core_Helper_View::meta_category_obj($current_category, 3);
            
            $tpl = 'page/topic/list.html';
            //$tpl = 'page/topic/index.html';
		}else{
		    $tpl = 'page/topic/index.html';
		}

		// 昨天的日期
		$yesterday = (int)date('Ymd' , strtotime('-1 day'));
		$this->stash['yesterday'] = $yesterday;

		// 分页链接
		$this->stash['pager_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time, $sort, '#p#');
		
		$this->stash['cid'] = $this->stash['category_id'];
		$this->stash['is_top'] = $is_top;
		
		$this->stash['current_category'] = $current_category;
		$this->stash['parent_category'] = $parent_category;
		
		return $this->to_html_page($tpl);
	}
	
	/**
	 * 生成高级检索链接
	 */
	protected function gen_advanced_links($category_id=0, $type=1, $time='all', $sort='latest', $page=1){
		$links = array();
		
		// 类别
        $links['all_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, 0, $time, $sort, $page);
		$links['reply_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, 3, $time, 7, $page);
		$links['stick_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, 1, $time, $sort, $page);
		$links['fine_url']  = Sher_Core_Helper_Url::topic_advance_list_url($category_id, 2, $time, $sort, $page);
		$links['active_url']  = Sher_Core_Helper_Url::topic_advance_list_url($category_id, 4, $time, $sort, $page);
		switch($type){
			case 1:
				$this->set_target_css_state('type_stick');
				break;
			case 2:
				$this->set_target_css_state('type_fine');
				break;
            case 3:
                $this->set_target_css_state('type_reply');
                break;
            case 4:
                $this->set_target_css_state('type_active');
                break;
			default:
                $this->set_target_css_state('type_all');
				break;
		}
		
		// 时间
		$links['time_all_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, 0, $sort, $page);
		$links['time_day_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, 1, $sort, $page);
		$links['time_week_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, 2, $sort, $page);
		$links['time_mouth_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, 3, $sort, $page);
		$links['time_year_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, 4, $sort, $page);
		switch($time){
			case 0:
				$this->set_target_css_state('time_all');
				break;
			case 1:
				$this->set_target_css_state('time_day');
				break;
			case 2:
				$this->set_target_css_state('time_week');
				break;	
			case 3:
				$this->set_target_css_state('time_mouth');
				break;
			case 4:
				$this->set_target_css_state('time_year');
				break;
			default:
				break;
		}        
        
		// 排序
		// 默认发帖时间
		$links['sort_default_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time, 0, $page);
		// 最近回复
		$links['sort_updated_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time,  7,$page);
		$links['sort_comment_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time, 2, $page);
        $links['sort_view_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time, 5, $page);
		$links['sort_favorite_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time, 3, $page);
		$links['sort_laud_url'] = Sher_Core_Helper_Url::topic_advance_list_url($category_id, $type, $time, 4, $page);
		
		switch($sort){
			case 2:
				$this->set_target_css_state('sort_comment');
				break;
			case 3:
				$this->set_target_css_state('sort_favorite');
				break;
			case 4:
				$this->set_target_css_state('sort_love');
				break;
			case 5:
				$this->set_target_css_state('sort_view');
				break;
			case 7:
				$this->set_target_css_state('sort_update');
				break;
			default:
                $this->set_target_css_state('sort_default');
				break;
		}
		
		$this->stash['links'] = $links;
	}
	
	/**
	 * 某产品的话题
	 */
	public function subject(){
		$id = (int)$this->stash['id'];
		
		$category = new Sher_Core_Model_Category();
		// 获取产品专区话题列表
		$category_id = Doggy_Config::$vars['app.product.topic_category_id'];
		$subject_category = $category->extend_load($category_id);
		// 获取父级分类
		$parent_category = $category->extend_load((int)$subject_category['pid']);
		
		$this->stash['category_id'] = $category_id;
		$this->stash['subject_category'] = $subject_category;
		$this->stash['parent_category'] = $parent_category;
		
		$product = new Sher_Core_Model_Product();
		$this->stash['product'] = & $product->extend_load($id);
		
		// 评论的链接URL
		$this->stash['pager_url'] = Sher_Core_Helper_Url::product_subject_url($id, '#p#');

		$this->stash['mode'] = 'create';
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_TOPIC;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();

		// 附件上传参数
		$this->stash['file_token'] = Sher_Core_Util_Image::qiniu_token(null, true);
		$this->stash['file_domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['file_asset_type'] = Sher_Core_Model_Asset::TYPE_FILE_TOPIC;
		$this->stash['file_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		// 评测对象
		if(isset($this->stash['tid'])){
			$this->stash['try_id'] = $this->stash['tid'];
		}
		
		// 判断来源
		if(isset($this->stash['ref']) && $this->stash['ref'] == 'dream'){
			$page_title = '提交创意';
			$this->stash['hide'] = 'hide';
		}else{
			$page_title = '发表主题';
		}
		$this->stash['page_title'] = $page_title;
		
		$this->editor_params();
		
		return $this->to_html_page('page/topic/subject_list.html');
	}
	
	/**
	 * 产品试用报告
	 */
	public function report(){
		$tid = (int)$this->stash['tid'];
		
		$try = new Sher_Core_Model_Try();
		$this->stash['try'] = $try->extend_load($tid);
		
		// 评测报告分类
		$this->stash['report_category_id'] = Doggy_Config::$vars['app.try.report_category_id'];
		
		// 分类名称
		$category = new Sher_Core_Model_Category();
		$subject_category = $category->extend_load($this->stash['report_category_id']);
		
		$this->stash['subject_category'] = $subject_category;
		
		// 分页链接
		$this->stash['pager_url'] = sprintf(Doggy_Config::$vars['app.url.topic'].'/report?tid=%d&page=#p#', $tid);
		
		return $this->to_html_page('page/topic/report_list.html');
	}
	
	/**
	 * 显示主题详情帖
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.topic'];
		if(empty($id)){
			return $this->show_message_page('访问的主题不存在！', $redirect_url);
		}
		// 是否允许编辑
		$editable = false;
		
		if(isset($this->stash['referer'])){
			$this->stash['referer'] = Sher_Core_Helper_Util::RemoveXSS($this->stash['referer']);
		}
		$tpl = 'page/topic/show.html';
		
		$model = new Sher_Core_Model_Topic();
		$topic = $model->load($id);
		
		if(empty($topic) || $topic['deleted']){
			return $this->show_message_page('访问的主题不存在或已被删除！', $redirect_url);
		}
		if($topic['verifyed'] == 0){
      $verify_show = false;
      if($this->visitor->id){
        if($this->visitor->id == $topic['user_id'] || $this->visitor->can_edit()){
          $verify_show = true;
        }
      }
      if(!$verify_show){
        return $this->show_message_page('访问的话题未审核！', $redirect_url);
      }
		}
		if($topic['published'] == 0){
      if(!($this->visitor->id && $this->visitor->id == $topic['user_id'])){
			  return $this->show_message_page('访问的话题未发布！', $redirect_url);
      }
		}
        if (!empty($topic)) {
            $topic = $model->extended_model_row($topic);
        }
		
		// 综合分类
		$this->stash['topic_category_official'] = Doggy_Config::$vars['app.topic_category_official'];
		// 产品分类
		$this->stash['topic_category_user'] = Doggy_Config::$vars['app.topic_category_user'];

		//添加网站meta标签
		$this->stash['page_title_suffix'] = sprintf("%s-太火鸟智能硬件社区", $topic['title']);
		if(!empty($topic['tags'])){
            $this->stash['page_keywords_suffix'] = sprintf("智能硬件社区,孵化需求,活动动态,品牌专区,产品评测,太火鸟,智能硬件,%s", $topic['tags'][0]);   
		}
		$this->stash['page_description_suffix'] = sprintf("【太火鸟话题】 %s", mb_substr($topic['strip_description'], 0, 140));
		
		// 增加pv++
		$inc_ran = rand(1,6);
		$model->increase_counter('view_count', $inc_ran, $id);

		$model->increase_counter('true_view_count', 1, $id);
		$model->increase_counter('web_view_count', 1, $id);
		
		// 当前用户是否有管理权限
		if ($this->visitor->id){
			if ($this->visitor->id == $topic['user_id'] || $this->visitor->can_edit){
				$editable = true;
			}

            // 验证用户关注关系
            $this->validate_ship($this->visitor->id, $topic['user_id']);
		}
		
		// 是否出现后一页按钮
	    if(isset($this->stash['referer'])){
            $this->stash['HTTP_REFERER'] = $this->current_page_ref();
	    }
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$topic['fid']);
		
		$this->stash['topic'] = &$topic;
		$this->stash['parent_category'] = $parent_category;
		$this->stash['editable'] = $editable;

    if($topic['_id']==109581){
      $this->stash['jimi'] = true;
    }
		
		// 判定是否产品话题
		if (isset($topic['target_id']) && !empty($topic['target_id'])){
			$product = new Sher_Core_Model_Product();
			$this->stash['product'] = & $product->extend_load($topic['target_id']);
        }elseif(isset($topic['active_id']) && !empty($topic['active_id'])){
			$active = new Sher_Core_Model_Active();
 			$this->stash['active'] = & $active->extend_load($topic['active_id']);    
        }
		
		// 是否参赛作品
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		if($topic['category_id'] == $this->stash['dream_category_id']){
			if($topic['created_on'] >= mktime(0,0,0,10,28,2014) && $topic['created_on'] <= mktime(23,59,59,12,20,2014)){
				$this->stash['is_match_idea'] = true;
			}
		}

    // 查看该贴子是否属于评论分享贴
    $is_comment_share = false;
    $comment_topic_ids = Doggy_Config::$vars['app.topic_comment_ids'];
    if(!empty($comment_topic_ids)){
      $comment_topic_arr = explode('|', $comment_topic_ids);
      for($i=0;$i<count($comment_topic_arr);$i++){
        if((int)$comment_topic_arr[$i]==$topic['_id']){
          $is_comment_share = true;
          break;
        }
      }
    }
    $this->stash['is_comment_share'] = $is_comment_share;
		
		// 评论参数
		$comment_options = array(
		  'comment_target_id' =>  $topic['_id'],
		  'comment_target_user_id' => $topic['user_id'],
		  'comment_type'  =>  2,
		  'comment_pager' =>  Sher_Core_Helper_Url::topic_view_url($id, '#p#')."#comment_top",
		  //是否显示上传图片/链接
		  'comment_show_rich' => 1,
		);
		$this->_comment_param($comment_options);
		
		// 投票部分代码
		$is_vote = 0;
		if(isset($topic['vote_id']) && !empty($topic['vote_id'])){
			$model_vote = new Sher_Core_Model_Vote();
			$voteOne = $model_vote->find_by_id(array('relate_id' => (int)$id));
			$vote_id = $voteOne['_id'];
			$vote = $model_vote->statistics((int)$vote_id);
			$this->stash['vote'] = &$vote;
			if($vote){
				$is_vote = 1;
			}
		}
		
		$can_vote = 0;
		if(isset($topic['vote_id']) && !empty($topic['vote_id'])){
			$model = new Sher_Core_Model_VoteRecord();
			$data = array();
			$data['vote_id'] = $topic['vote_id'];
			$data['user_id'] = $this->visitor->id;
			$data['relate_id'] = (int)$id;
			$voteRecord = $model->find($data);
			if(count($voteRecord)){
				$can_vote = 1;
			}
		}
		
		// 添加显示权限(登陆状态、发帖本人、星级会员)
		$vote_show = 0;
		if($this->visitor->id && (int)$this->visitor->id == (int)$topic['user_id'] && $this->visitor->mentor){
			$vote_show = 1;
		}
		
		$this->stash['is_vote'] = $is_vote;
		$this->stash['is_vote'] = $is_vote;
		$this->stash['can_vote'] = $can_vote;
        
        // 跳转楼层
        $floor = (int)$this->stash['floor'];
        if($floor){
            $new_page = ceil($floor/10);
            $this->stash['page'] = $new_page;
        }
		
		// 加｜
		if ( !empty($topic['top']) || !empty($topic['fine']) || !empty($topic['stick']) ){
			$this->stash['tline'] = true;
		}
        
		return $this->to_html_page($tpl);
	}
	
    
  /**
   * 签到
   */
  public function sign(){
    // 转到社区首页
      return $this->index();
      $this->stash['has_sign'] = false;
      $user_model = new Sher_Core_Model_User();
      $user = $user_model->load((int)$this->visitor->id);
      if(!empty($user)){
          $this->stash['user'] = $user_model->extended_model_row($user);
      }
      $user_sign_model = new Sher_Core_Model_UserSign();
      $user_sign = $user_sign_model->extend_load((int)$this->visitor->id);
      $redirect_url = Doggy_Config::$vars['app.url.topic'];
      if(empty($user_sign)){
        return $this->show_message_page('数据不存在！', $redirect_url);
      }

      $today = (int)date('Ymd');
      $month = (int)date('Ym');
      $yesterday = (int)date('Ymd', strtotime('-1 day'));
      if($user_sign['last_date'] == $yesterday){
          $continuity_times = $user_sign['sign_times'];
      }elseif($user_sign['last_date'] == $today){
          $this->stash['has_sign'] = true;
          $continuity_times = $user_sign['sign_times'];
      }

      // 公告---取块内容
      $notice = Sher_Core_Util_View::load_block('sign_notice', 2);
      $this->stash['notice'] = $notice;

      $type = isset($this->stash['type']) ? (int)$this->stash['type'] : 0;

      $this->stash['day'] = 0;
      $this->stash['month'] = 0;
      $this->stash['month'] = 0;     
      $this->stash['type'] = $type;
      if($type==0){
        $this->stash['day'] = $today;
      }elseif($type==1){
        $this->stash['month'] = $month;
      }

      $size = $this->stash['size'] = 30;

      $pager_url = $pager_url = sprintf(Doggy_Config::$vars['app.url.topic'].'/sign?type=%d&day=%d&month=%d&page=#p#', $type, $this->stash['day'], $this->stash['month']);
      $this->stash['pager_url'] = $pager_url;

      $this->stash['user_sign'] = $user_sign;
      return $this->to_html_page('page/topic/sign.html');
    }
    
	/**
	 * 推荐
	 */
	public function ajax_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
            // 验证是否具有权限
            if(!$this->visitor->can_edit()){
                return $this->ajax_json('抱歉，你没有权限操作此项！', true);
            }
			$model = new Sher_Core_Model_Topic();
			$model->mark_as_stick((int)$id, Sher_Core_Model_Topic::STICK_EDITOR, array('current_user_id'=>$this->visitor->id));
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 取消推荐
	 */
	public function ajax_cancel_stick(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
            // 验证是否具有权限
            if(!$this->visitor->can_edit()){
                return $this->ajax_json('抱歉，你没有权限操作此项！', true);
            }
            
			$model = new Sher_Core_Model_Topic();
			$model->mark_cancel_stick((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
    
	/**
	 * 精华
	 */
	public function ajax_fine(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
            // 验证是否具有权限
            if(!$this->visitor->can_edit()){
                return $this->ajax_json('抱歉，你没有权限操作此项！', true);
            }
			$model = new Sher_Core_Model_Topic();
			$ok = $model->mark_as_fine((int)$id, array('current_user_id'=>$this->visitor->id));
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
    
	/**
	 * 取消精华
	 */
	public function ajax_cancel_fine(){
		$id = $this->stash['id'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
            // 验证是否具有权限
            if(!$this->visitor->can_edit()){
                return $this->ajax_json('抱歉，你没有权限操作此项！', true);
            }
			$model = new Sher_Core_Model_Topic();
			$model->mark_cancel_fine((int)$id);
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 置顶
	 */
	public function ajax_top(){
		if(empty($this->stash['id'])){
			return $this->ajax_notification('主题不存在！', true);
		}
		$id = $this->stash['id'];
        $tv = $this->stash['tv'];
        
		Doggy_Log_Helper::debug("Top Topic [$id][$tv]!");
		try{
			if (!$this->visitor->can_edit()){
				return $this->ajax_notification('抱歉，你没有权限进行此操作！', true);
			}
			
			$model = new Sher_Core_Model_Topic();
            $row = $model->load((int)$id);
            if(empty($row)){
                return $this->ajax_notification("主题[$id]不存在！", true);
            }
            
			$ok = $model->mark_as_top((int)$id, (int)$tv);
			if($ok){
                $old_top = $row['top'];
                if ($tv == Sher_Core_Model_Topic::TOP_CATEGORY){
                    $key_id = Sher_Core_Util_Constant::top_topic_category_key($row['category_id']);
                    $remove_key_id = Sher_Core_Util_Constant::DIG_TOPIC_TOP;
                }else{
                    $key_id = Sher_Core_Util_Constant::DIG_TOPIC_TOP;
                    $remove_key_id = Sher_Core_Util_Constant::top_topic_category_key($row['category_id']);
                }
                
                $diglist = new Sher_Core_Model_DigList();
                // 先从推荐表里删除
                if($old_top){
                    $diglist->remove_item($remove_key_id, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
                }
				// 添加到站内推荐列表
				$diglist->add_dig($key_id, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 取消置顶
	 */
	public function ajax_cancel_top(){
		$id = $this->stash['id'];
        $tv = $this->stash['tv'];
		if(empty($this->stash['id'])){
			return $this->ajax_json('主题不存在！', true);
		}
		
		try{
			if (!$this->visitor->can_edit()){
				return $this->ajax_json('抱歉，你没有权限进行此操作！', true);
			}
			
			$model = new Sher_Core_Model_Topic();
      $row = $model->load((int)$id);
      if(empty($row)){
          return $this->ajax_notification("主题[$id]不存在！", true);
      }

			$ok = $model->mark_cancel_top((int)$id);
			if ($ok) {
        $old_top = $row['top'];
				$diglist = new Sher_Core_Model_DigList();
        if ($tv == Sher_Core_Model_Topic::TOP_CATEGORY){
            $key_id = Sher_Core_Util_Constant::top_topic_category_key($row['category_id']);
				    $diglist->remove_item($key_id, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
        }else{
            $key_id = Sher_Core_Util_Constant::DIG_TOPIC_TOP;
				    $diglist->remove_item($key_id, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
        }

			}
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_json('操作失败,请重新再试', true);
		}
		
		return $this->ajax_json('操作成功');
	}
	
	/**
	 * 编辑器参数
	 */
	protected function editor_params() {
		$callback_url = Doggy_Config::$vars['app.url.qiniu.onelink'];
		$this->stash['editor_token'] = Sher_Core_Util_Image::qiniu_token($callback_url, false, true);
		$new_pic_id = new MongoId();
		$this->stash['editor_pid'] = (string)$new_pic_id;

		$this->stash['editor_domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['editor_asset_type'] = Sher_Core_Model_Asset::TYPE_EDITOR_TOPIC;
	}
	
	/**
	 * 提交创意
	 */
	public function submit(){
		
		$cid = $this->stash['cid'];
		// 是否为一级分类
		$is_top = true;
		$pid = 0;
		$current_category = array();
		$parent_category = array();

		$category = new Sher_Core_Model_Category();
		// 获取当前分类信息
        if($cid){
            $current_category = $category->load((int)$cid);
            // 存在父级分类，标识是二级分类
            if (!empty($current_category['pid'])){
                $is_top = false;
                // 获取父级分类
                $parent_category = $category->extend_load((int)$current_category['pid']);
            }   
        }else{
            $is_top = true;
            $parent_category = 0;
            $current_category = 0;
        }

		// 产品分类
		$this->stash['topic_category_product'] = Doggy_Config::$vars['app.topic_category_product'];

		$this->stash['is_top'] = $is_top;
		$this->stash['current_category'] = $current_category;
		$this->stash['parent_category'] = $parent_category;
		
		$this->stash['mode'] = 'create';
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_TOPIC;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();

		// 附件上传参数
		$this->stash['file_token'] = Sher_Core_Util_Image::qiniu_token(null, true);
		$this->stash['file_domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['file_asset_type'] = Sher_Core_Model_Asset::TYPE_FILE_TOPIC;
		$this->stash['file_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		// 评测对象
		if(isset($this->stash['tid'])){
			$this->stash['try_id'] = $this->stash['tid'];
		}
		
		// 判断来源
		if(isset($this->stash['ref']) && $this->stash['ref'] == 'dream'){
			$page_title = '提交创意';
			$this->stash['hide'] = 'hide';
		}else{
			$page_title = '发表主题';
		}
		$this->stash['page_title'] = $page_title;
		
		$this->editor_params();
		
		return $this->to_html_page('page/topic/submit.html');
	}
	
	/**
	 * 编辑修改主题
	 */
	public function edit(){
		if(empty($this->stash['id'])){
			return $this->show_message_page('编辑的主题不存在！', true);
		}
		$model = new Sher_Core_Model_Topic();
		$topic = $model->load((int)$this->stash['id']);

		// 仅编辑权限或本人具有删除权限
		if (!$this->visitor->can_edit() && !($topic['user_id'] == $this->visitor->id)){
			return $this->show_message_page('你没有权限编辑的该主题！', true);
		}
        if (!empty($topic)) {
            $topic = $model->extended_model_row($topic);
        }
		
		// 是否为一级分类
		$is_top = false;
		$current_category = array();
		$parent_category = array();
		
		$category = new Sher_Core_Model_Category();
		// 获取当前分类信息
		$current_category = $category->load((int)$topic['category_id']);
		// 获取父级分类
		//$parent_category = $category->load((int)$topic['fid']);

		$this->stash['is_top'] = $is_top;
		$this->stash['current_category'] = $current_category;
		$this->stash['parent_category'] = 0;
		//$this->stash['parent_category'] = $parent_category;
		
		$this->stash['cid'] = $topic['category_id'];
		$this->stash['try_id'] = $topic['try_id'];
		
		$this->stash['mode'] = 'edit';
		$this->stash['topic'] = $topic;
		
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_TOPIC;

		// 附件上传参数
		$this->stash['file_token'] = Sher_Core_Util_Image::qiniu_token(null, true);
		$this->stash['file_domain'] = Sher_Core_Util_Constant::STROAGE_TOPIC;
		$this->stash['file_asset_type'] = Sher_Core_Model_Asset::TYPE_FILE_TOPIC;
		$this->stash['file_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		$this->editor_params();
		
		// 判断来源
		if(isset($topic['category_id']) && $topic['category_id'] == Doggy_Config::$vars['app.topic.dream_category_id']){
			//$page_title = '提交创意';
			$page_title = '发表话题';
			//$this->stash['hide'] = 'hide';
		}else{
			$page_title = '发表话题';
		}
		$this->stash['page_title'] = $page_title;
		
		return $this->to_html_page('page/topic/submit.html');
	}
	
	/**
	 * 保存主题信息
	 */
	public function save(){
		
		// 禁用用户无法操作
		if(!$this->stash["visitor"]['state']){
			return $this->ajax_json('您不能添加话题信息！', true);
		}
		
		// 验证数据
		if(empty($this->stash['title'])){
			return $this->ajax_json('标题不能为空！', true);
		}

		$id = isset($this->stash['_id']) ? (int)$this->stash['_id'] : 0;

    // 用户发表频率、次数限制
    if(empty($id)){
      if(empty($this->visitor->quality)){
        $pub_is_limit = Sher_Core_Helper_Util::report_filter_limit($this->visitor->id, 1);
        if($pub_is_limit['success']){
          return $this->ajax_json($pub_is_limit['msg'], true);   
        }     
      }
    }
		
		$mode = 'create';
		$data = array();
    // 关闭审核状态,默认不审核
    // $data['verifyed'] = 0;
		$data['_id'] = $id;
		$data['title'] = $this->stash['title'];
		$data['description'] = $this->stash['description'];
		
		//自动添加关键词内链
		$data['description'] = Sher_Core_Helper_Util::gen_inlink_keyword($data['description'], 1);
		$data['tags'] = $this->stash['tags'];
		$data['category_id'] = $this->stash['category_id'];
		
		$data['cover_id'] = $this->stash['cover_id'];
    $data['target_id'] = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
		
		$data['try_id'] = $this->stash['try_id'];
		$data['published'] = (int)$this->stash['published'];
    $old_published = isset($this->stash['old_published'])?(int)$this->stash['old_published']:1;

		$data['short_title'] = isset($this->stash['short_title'])?$this->stash['short_title']:'';
		$data['t_color'] = isset($this->stash['t_color'])?(int)$this->stash['t_color']:0;

		$data['source'] = isset($this->stash['source'])?$this->stash['source']:'';
		$data['attrbute'] = isset($this->stash['attrbute'])?(int)$this->stash['attrbute']:0;
		$data['is_zaker_rss'] = isset($this->stash['is_zaker_rss'])?1:0;
		
		// 检测编辑器图片数
		$file_count = isset($this->stash['file_count']) ? (int)$this->stash['file_count'] : 0;
        $newadd_asset_ids = isset($this->stash['newadd_asset_ids'])?$this->stash['newadd_asset_ids']:'';
        
		// 检查是否有图片
		if(isset($this->stash['asset'])){
			$data['asset'] = $this->stash['asset'];
		}else{
			$data['asset'] = array();
		}

		// 检查是否有附件
		if(isset($this->stash['file_asset'])){
			$data['file_asset'] = $this->stash['file_asset'];
		}else{
			$data['file_asset'] = array();
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				$ok = $model->apply_and_save($data);
				$topic = $model->get_data();
				$id = $topic['_id'];
				
				// 更新用户主题数量
				$this->visitor->inc_counter('topic_count', $data['user_id']);
				
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			// 上传成功后，更新所属的图片
			if(isset($data['asset']) && !empty($data['asset'])){
				$this->update_batch_assets($data['asset'], $id);
			}
            // 上传成功后，更新编辑器图片
            if(!empty($newadd_asset_ids)){
                $newadd_asset = array_filter(array_unique(preg_split('/[,]+/u', $newadd_asset_ids)));
                $this->update_batch_assets($newadd_asset, $id);
            }
			// 上传成功后，更新所属的附件
			if(isset($data['file_asset']) && !empty($data['file_asset'])){
				$this->update_batch_assets($data['file_asset'], $id);
			}
			
			// 保存成功后，更新编辑器图片
			Doggy_Log_Helper::debug("Upload file count[$file_count].");
			if(!empty($this->stash['file_id'])){
				$model->update_editor_asset($id, $this->stash['file_id']);
			}
            
            // 更新全文索引
            if($data['published'] == 1){
                Sher_Core_Helper_Search::record_update_to_dig((int)$id, 1);
            }
			//更新百度推送
			if($mode == 'create' && $data['published'] == 1){
			  Sher_Core_Helper_Search::record_update_to_dig((int)$id, 10); 
			}
            // 由草稿转为发布状态
            if($mode == 'edit' && $old_published == 0 && $data['published'] == 1){
                Sher_Core_Helper_Search::record_update_to_dig((int)$id, 10);
            }
				
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("创意保存失败：".$e->getMessage());
			return $this->ajax_json('创意保存失败:'.$e->getMessage(), true);
		}
		
		$redirect_url = Sher_Core_Helper_Url::topic_view_url($id);
		return $this->ajax_json('保存成功,您已经获得10经验值！.', false, $redirect_url);
	}
	
	/**
	 * 保存产品话题信息
	 */
	public function ajax_save(){
		
		// 禁用用户无法操作
		if(!$this->stash["visitor"]['state']){
			return $this->ajax_json('您不能添加话题信息！', true);
		}
		
		// 验证数据
		$target_id = $this->stash['target_id'];
		if(empty($this->stash['title']) || empty($this->stash['description'])){
			return $this->ajax_json('标题和内容不能为空！', true);
		}
		$id = (int)$this->stash['_id'];
		$mode = 'create';
		
		$data = array();
		
		$data['_id'] = $id;
		$data['title'] = $this->stash['title'];
		$data['description'] = $this->stash['description'];
		$data['target_id'] = (int)$this->stash['target_id'];
		
		// 产品话题分类Id
        if(isset($this->stash['evaluating'])){
            // 产品评测
  		    $data['category_id'] = (int)Doggy_Config::$vars['app.product.topic_evaluating_category_id'];  
        }else{
            // 产品讨论
            $data['category_id'] = (int)Doggy_Config::$vars['app.product.topic_category_id'];
        }

		try{
			$model = new Sher_Core_Model_Topic();
			// 新建记录
			if(empty($id)){
				$data['user_id'] = (int)$this->visitor->id;
				
				$ok = $model->apply_and_save($data);
				$topic = $model->get_data();
				
				$id = $topic['_id'];
				
				// 更新用户主题数量
				$this->visitor->inc_counter('topic_count', $data['user_id']);
				
			}else{
				$mode = 'edit';
				$ok = $model->apply_and_update($data);
			}
			
			if(!$ok){
				return $this->ajax_json('保存失败,请重新提交', true);
			}
			
			$this->stash['topic'] = &DoggyX_Model_Mapper::load_model($id,'Sher_Core_Model_Topic');

			// 更新到索引
			Sher_Core_Helper_Search::record_update_to_dig((int)$id, 1);
			
			//更新百度推送
			if($mode == 'create'){
			  Sher_Core_Helper_Search::record_update_to_dig((int)$id, 10); 
			}
			
		}catch(Sher_Core_Model_Exception $e){
			Doggy_Log_Helper::warn("话题保存失败：".$e->getMessage());
			return $this->ajax_json('话题保存失败:'.$e->getMessage(), true);
		}
		
		return $this->to_taconite_page('ajax/product_topic.html');
	}
	
	/**
	 * 保存投票信息
	 */
	public function save_vote(){
		
		$back = array(0,0,0);
		$field_name = 'nums';
		
		$vote = json_decode('['.$this->stash['vote'].']',true);
		$vote = $vote[0];
		
		// 验证拒绝重复投票
		$model_vote_record = new Sher_Core_Model_VoteRecord();
		$res_vote_record = $model_vote_record->find(array('vote_id' => (int)$vote['vote_id'],'user_id' => (int)$vote['user_id'],'relate_id' => (int)$vote['topic_id']));
		if($res_vote_record){
			echo 1;exit;
		}
		
		$problem = json_decode('['.$this->stash['problem'].']',true);
		$problem = $problem[0];
		
		// 更新投票次数
		$model_vote = new Sher_Core_Model_Vote();
		if($model_vote->inc_counter($field_name, (int)$vote['vote_id'], $inc=1)){
			$back[0] = 1;
		}
		
		$vote_record = array();
		$i = 0;
		$model_answer = new Sher_Core_Model_Answer();
		foreach($problem as $k => $v){
			foreach($v["answer"] as $key => $value){
				$vote_record[$i]['vote_id'] = (int)$vote['vote_id'];
				$vote_record[$i]['user_id'] = (int)$vote['user_id'];
				$vote_record[$i]['relate_id'] = (int)$vote['topic_id'];
				$vote_record[$i]['problem_id'] = $v['id'];
				$vote_record[$i]['answer_id'] = $value;
				// 更新答案次数
				if($model_answer->inc_counter('nums', $value, $inc=1)){
					$back[1]++;
				}
				$i++;
			}
			$i++;
		}
		
		// 添加投票信息记录
		foreach($vote_record as $v){
			if($model_vote_record->create($v)){
				$back[2]++;
			}
		}
		
		if(!$back[0] || $back[1] !== count($vote_record) || $back[2] !== count($vote_record)){
			echo 0;exit;
		}
		
		$model = new Sher_Core_Model_Vote();
		$result = $model->statistics((int)$vote['vote_id']);
		echo json_encode($result);
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
		}
	}
	
	/**
	 * 删除主题
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			$topic = $model->load((int)$id);
			
			// 仅编辑权限或本人具有删除权限
			if ($this->visitor->can_edit() || $topic['user_id'] == $this->visitor->id){
				$model->mark_remove((int)$id);
				
				// 删除关联对象
				$model->mock_after_remove($id);
				
				// 从置顶列表中删除
				if ($topic['top']){
					$diglist = new Sher_Core_Model_DigList();
					$diglist->remove_item(Sher_Core_Util_Constant::DIG_TOPIC_TOP, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
				}
				
				// 更新所属分类: 主题数、回复数
				$category = new Sher_Core_Model_Category();
				
				$category->dec_counter('total_count', $topic['category_id']);
				$category->dec_counter('total_count', $topic['fid']);
				$category->dec_counter('reply_count', $topic['category_id'], false, $topic['comment_count']);
				$category->dec_counter('reply_count', $topic['fid'], false, $topic['comment_count']);
				
				// 更新用户主题数量
				$this->visitor->dec_counter('topic_count', $topic['user_id']);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		// 删除成功后返回URL
		$this->stash['redirect_url'] = Doggy_Config::$vars['app.url.topic'];
		$this->stash['ids'] = array($id);
		
		return $this->to_taconite_page('ajax/delete.html');
	}
	
	/**
	 * ajax删除主题
	 */
	public function ajax_del(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('主题不存在！', true);
		}
		
		try{
			$model = new Sher_Core_Model_Topic();
			$topic = $model->load((int)$id);
			
			// 仅管理员或本人具有删除权限
			if ($this->visitor->can_edit() || $topic['user_id'] == $this->visitor->id){
				$model->mark_remove((int)$id);
				
				// 删除关联对象
				$model->mock_after_remove($id);
				
				// 从置顶列表中删除
				if ($topic['top']){
					$diglist = new Sher_Core_Model_DigList();
					$diglist->remove_item(Sher_Core_Util_Constant::DIG_TOPIC_TOP, (int)$id, Sher_Core_Util_Constant::TYPE_TOPIC);
				}
				
				// 更新所属分类: 主题数、回复数
				$category = new Sher_Core_Model_Category();
				
				$category->dec_counter('total_count', $topic['category_id']);
				$category->dec_counter('total_count', $topic['fid']);
				$category->dec_counter('reply_count', $topic['category_id'], false, $topic['comment_count']);
				$category->dec_counter('reply_count', $topic['fid'], false, $topic['comment_count']);
				
				// 更新用户主题数量
				$this->visitor->dec_counter('topic_count', $topic['user_id']);
			}
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}

		$this->stash['ids'] = array($id);
		
		return $this->to_taconite_page('ajax/del_ok.html');
	}
	
	/**
	 * 删除某个附件
	 */
	public function delete_asset(){
		$id = $this->stash['id'];
		$asset_id = $this->stash['asset_id'];
		if (empty($id) || empty($asset_id)){
			return $this->ajax_note('附件不存在！', true);
		}
		$model = new Sher_Core_Model_Topic();
		$model->delete_asset($id, $asset_id);
		
		return $this->to_taconite_page('ajax/delete_asset.html');
	}

  /**
   * 验证关系
   */
  protected function validate_ship($current_user_id, $auther_id){
    // 验证关注关系
    $ship = new Sher_Core_Model_Follow();
    $is_ship = $ship->has_exist_ship($current_user_id, $auther_id);
    $this->stash['is_ship'] = $is_ship;
  }
    
    /**
     * 评论参数
     */
    protected function _comment_param($options){
        $this->stash['comment_target_id'] = $options['comment_target_id'];
        $this->stash['comment_target_user_id'] = $options['comment_target_user_id'];
        $this->stash['comment_type'] = $options['comment_type'];
		// 评论的链接URL
		$this->stash['pager_url'] = isset($options['comment_pager'])?$options['comment_pager']:0;

        // 是否显示图文并茂
        $this->stash['comment_show_rich'] = isset($options['comment_show_rich'])?$options['comment_show_rich']:0;
		// 评论图片上传参数
		$this->stash['comment_token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['comment_domain'] = Sher_Core_Util_Constant::STROAGE_COMMENT;
		$this->stash['comment_asset_type'] = Sher_Core_Model_Asset::TYPE_COMMENT;
		$this->stash['comment_pid'] = Sher_Core_Helper_Util::generate_mongo_id();
  }

}
