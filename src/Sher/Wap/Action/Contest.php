<?php
/**
 * 大赛-十万火计
 * @author purpen
 */
class Sher_Wap_Action_Contest extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
		'category_id' => 0,
	);
	
	protected $exclude_method_list = array('execute','dream', 'dream2', 'topic', 'allist', 'allist2', 'get_list', 'show', 'rank', 'ajax_fetch_top_province', 'ajax_fetch_top_college', 'ajax_load_colleges','matcht','custom','about3', 'show');
	
	/**
	 * 社区入口
	 */
	public function execute(){
		return $this->dream2();
	}
	
	/**
	 * 十万火计
	 */
	public function dream(){
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('wap/match.html');
	}

	/**
	 * 十万火计 第２季
	 */
  public function dream2(){
		return $this->matcht();
		//$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		//$this->stash['start_time'] = mktime(0,0,0,2,10,2015);
		//$this->stash['end_time'] = mktime(23,59,59,6,20,2015);
		
		//return $this->to_html_page('wap/contest/match2.html');
	}
	
	/**
	 * 十万火计 第２季
	 */
	public function matcht(){
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		
		return $this->to_html_page('wap/contest/matcht.html');
	}
	
	/**
	 * 十万火计--第二季 作品排行
	 */
  public function rank() {
		$this->set_target_css_state('active2');
		$category_id = $this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
    $page = (int)$this->stash['page'];
    $size = 6;
    //排行
    $current_num = ($page-1)*$size;

    //大学人气排行
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
      //相关作品
      $stuffs = $stuff_mode->find(array('from_to'=>1, 'fid'=>$category_id, 'college_id'=>(int)$val['target_id']), array('page'=>1,'size'=>2, 'sort'=>array('love_count'=>-1)));
      $stuffs = $stuff_mode->extend_load_all($stuffs);
      $data[$key]['stuffs'] = $stuffs;
    }

    $this->stash['colleges'] = $data;
		return $this->to_html_page('wap/contest/rank.html');
	}
	
	/**
	 * 全部创意列表
	 */
	public function allist(){
		$this->set_target_css_state('allist');
		
		$page = "?page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap', 'dream', 'allist').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.topic.dream_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('wap/match_list.html');
	}

	/**
	 * 全部创意列表 第２季
	 */
	public function allist2(){
		$this->set_target_css_state('allist');
		
		$page = "?page=#p#";
		$pager_url = Sher_Core_Helper_Url::build_url_path('app.url.wap', 'contest', 'allist2').$page;
		$this->stash['pager_url'] = $pager_url;
		
		$this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
		
		$this->stash['start_time'] = mktime(0,0,0,10,28,2014);
		$this->stash['end_time'] = mktime(23,59,59,12,20,2014);
		
		return $this->to_html_page('wap/contest/list2.html');
	}
	
	/**
	 * 提交创意
	 */
	public function submit(){
		$top_category_id = Doggy_Config::$vars['app.contest.dream2_category_id'];

		$this->stash['cid'] = $top_category_id;
		$this->stash['mode'] = 'create';
		
		// 获取父级分类
		$category = new Sher_Core_Model_Category();
		$parent_category = $category->extend_load((int)$top_category_id);
		
		$this->stash['parent_category'] = $parent_category;
		$this->stash['mode'] = 'create';
		
		// 图片上传参数
		$this->stash['token'] = Sher_Core_Util_Image::qiniu_token();
		$this->stash['domain'] = Sher_Core_Util_Constant::STROAGE_STUFF;
		$this->stash['asset_type'] = Sher_Core_Model_Asset::TYPE_STUFF;
		$new_file_id = new MongoId();
		$this->stash['new_file_id'] = (string)$new_file_id;
		$this->stash['pid'] = Sher_Core_Helper_Util::generate_mongo_id();
		
		return $this->to_html_page('wap/contest/submit.html');
	}

  /**
   * 统计
   * ajax获取省份前3
   */
  public function ajax_fetch_top_province(){
    $model = new Sher_Core_Model_SumRecord();
    $query['type'] = Sher_Core_Model_SumRecord::TYPE_PRO;
    $options['page'] = 1;
    $options['size'] = 6;
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
    //背景色
    $bg_color = array('#2f87d9','green','orange','gray','#2f87d9');
    $college_mode = new Sher_Core_Model_College();
    foreach($data as $key=>$val){
      $data[$key]['percent'] = (int)($val['match2_love_count']/(float)$total_love_count*100);
      $data[$key]['bg_color'] = $bg_color[$key];
      $college = $college_mode->find_by_id((int)$val['target_id']);
      $data[$key]['name'] = $college['name'];
    }
    $this->stash['college_data'] = $data;
    return $this->to_taconite_page('wap/contest/match_college_graph.html');
  }

  /**
   * ajax加载大学列表
   */
  public function ajax_load_colleges(){
    $category_id = $this->stash['dream_category_id'] = Doggy_Config::$vars['app.contest.dream2_category_id'];
    $page = (int)$this->stash['page'];
    $size = 6;
    //排行
    $current_num = ($page-1)*$size;

    //大学人气排行
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
      //相关作品
      $stuffs = $stuff_mode->find(array('from_to'=>1, 'fid'=>$category_id, 'college_id'=>(int)$val['target_id']), array('page'=>1,'size'=>2, 'sort'=>array('love_count'=>-1)));
      $stuffs = $stuff_mode->extend_load_all($stuffs);
      $data[$key]['stuffs'] = $stuffs;
    }

    $this->stash['colleges'] = $data;
    return $this->to_taconite_page('wap/contest/match_college_list.html');
  }
	
  	/**
   * 反向定制
   */
	public function custom(){
		return $this->to_html_page('wap/contest/custom.html');
	}
	
	public function about3(){
		return $this->to_html_page('wap/contest/about3.html');
	}

  /**
   * 大赛详情
   */
  public function show(){
    $id = $this->stash['id'];

    $redirect_url = sprintf("%s/contest/about3", Doggy_Config::$vars['app.url.wap']);

		if(empty($id)){
			return $this->show_message_page('缺少请求参数！', $redirect_url);
		}
    
    $model = new Sher_Core_Model_Contest();
    $contest = $model->extend_load((int)$id);

		if(empty($contest)){
			return $this->show_message_page('访问的主题不存在！', $redirect_url);
		}

		// 增加pv++
		$model->increase_counter('view_count', 1, $contest['_id']);
        
    $this->stash['contest'] = $contest;

    $render = sprintf("wap/match/%s.html", $contest['short_name']);
        
    return $this->to_html_page($render);
  }


}

