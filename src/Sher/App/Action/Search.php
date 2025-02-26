<?php
/**
 * 高级搜索
 * @author purpen
 */
class Sher_App_Action_Search extends Sher_App_Action_Base {
    
    public $stash = array(
		'page' => 1,
    'size' => 20,
		'q' => '',
		'ref' => '',
		't' => 0,
		'index_name' => 'full',
    'evt' => 'content',
    'db' => 'phenix',
    's' => 1,
    'asc' => 0,
	);

	protected $exclude_method_list = array('execute', 'xc', 'ajax_fetch_more');
	
    public function execute() {
      return $this->xc();
       	$words = Sher_Core_Service_Search::instance()->check_query_string($this->stash['q']);
        return $this->_display_search_list($words);
	}

  /**
   * 迅搜引擎,不走数据库/图片和用户名需要查询数据库
   */
  public function xc(){
    $db = $this->stash['db'];
    // 全文搜索/标签搜索
    $evt = $this->stash['evt'];
    $sort = (int)$this->stash['s'];

    if($evt=='tag'){
      $this->stash['evt_s'] = '标签';
    }else{
      $this->stash['evt_s'] = '内容';  
    }

    //搜索类型
 		if($this->stash['t'] == 1){
			$this->set_target_css_state('product');
		}elseif($this->stash['t'] == 2){
			$this->set_target_css_state('topic');
		}elseif($this->stash['t'] == 4){
			$this->set_target_css_state('stuff');
		}elseif($this->stash['t'] == 5){
			$this->set_target_css_state('vote');
    }else{
      $this->set_target_css_state('all');
    }

    $q = $this->stash['q'];

    $options = array(
      'page' => $this->stash['page'],
      'size' => $this->stash['size'],
      'evt'  => $evt,
      'sort' => $this->stash['s'],
      'asc'  => $this->stash['asc'],
      't'    => $this->stash['t'],
    );

    if($sort==0){
  		$this->set_target_css_state('relation');
    }elseif($sort==1){
   		$this->set_target_css_state('lastest');    
    }
    
    $result = Sher_Core_Util_XunSearch::search($q, $options, $db);
    if($result['success']){
      $user_model = new Sher_Core_Model_User();
      $asset_model = new Sher_Core_Model_Asset();
      foreach($result['data'] as $k=>$v){
        // 获取用户信息
        if($v['user_id']){
          $user = $user_model->find_by_id((int)$v['user_id']);
          $result['data'][$k]['nickname'] = $user['nickname'];
          $result['data'][$k]['home_url'] = Sher_Core_Helper_Url::user_home_url($user['_id']);
        }

        //描述内容过滤
        $result['data'][$k]['content'] = strip_tags($v['high_content'], '<em>');

        // 生成路径
        switch($v['kind']){
          case 'Stuff':
            $result['data'][$k]['view_url'] = Sher_Core_Helper_Url::stuff_view_url($v['oid']);
            break;
          case 'Topic':
            $result['data'][$k]['view_url'] = Sher_Core_Helper_Url::topic_view_url($v['oid']);
            break;
          case 'Product':
            $result['data'][$k]['view_url'] = Sher_Core_Helper_Search::gen_view_url($v['cid'], $v['oid']);
            break;
          default:
            $result['data'][$k]['view_url'] = '#';
        }

        //封面图
        if($v['cover_id']){
          $result['data'][$k]['asset'] = $asset_model->extend_load($v['cover_id']);
        }

        // 获取对象属性
        $result['data'][$k]['kind_name'] = Sher_Core_Helper_Search::kind_name($v['kind'], $v['cid']);

        // 获取asset_type
        $result['data'][$k]['asset_type'] = Sher_Core_Helper_Search::gen_asset_type($v['kind']);

      }

      $pager_url = sprintf('%s/search?t=%d&q=%s&evt=%s&size=%d&sort=%d&page=#p#', Doggy_Config::$vars['app.url.domain'], $this->stash['t'], $this->stash['q'], $this->stash['evt'], $this->stash['size'], $this->stash['s']);
      
      $this->stash['pager_url'] = $pager_url;
      $this->stash['result'] = $result;

    }else{
      
    }

		return $this->to_html_page('page/search.html');
  }
	
	/**
	 * 搜索结果页
	 */
	protected function _display_search_list($words){
		$this->stash['has_scws'] = false;
        if(!empty($words)){
            $this->stash['has_scws'] = true;
            $query_string = $this->stash['q'];
            foreach ($words as $k=>$v){
                $query_string = str_replace($v,"<b class='ui magenta text'>{$v}</b>", $query_string);
            }
            $this->stash['highlight'] = $query_string;
        }
        // 搜索来源标记
        $search_ref = $this->stash['ref'];
        $visitor_id = $this->visitor->id;

        // key for cache search result
        $this->stash['search_result_key'] = md5($this->stash['q']).'::'.$this->stash['page'];
        
        if($this->stash['index_name'] == 'tags'){
        	$this->stash['pager_url'] = Sher_Core_Helper_Url::build_url_path('app.url.tag', $this->stash['t'], $this->stash['q']).'p#p#.html';
        }else{
        	$this->stash['pager_url']  = Sher_Core_Helper_Url::build_url_path('app.url.search', $this->stash['t'], $this->stash['q']).'p#p#.html';
        	$this->stash['index_name'] = 'full';
        }
		
		if($this->stash['t'] == 1){
			$this->set_target_css_state('product');
		}elseif($this->stash['t'] == 2){
			$this->set_target_css_state('topic');
		}elseif($this->stash['t'] == 4){
			$this->set_target_css_state('stuff');
		}elseif($this->stash['t'] == 5){
			$this->set_target_css_state('vote');
		}
        
		return $this->to_html_page('page/search_old.html');
	}
	
	/**
     * 标签结果页
     */
    public function tag(){
    	$tag = $this->stash['q'];
    	$page = $this->stash['page'];
		
    	if(empty($sort)){
    		$sort = 'latest';
    	}
		
    	$this->stash['index_name'] = 'full';
    	$this->stash['page_tag_cache'] = 'tag_'.md5($tag).'s'.$sort.'p'.$page;
    	$this->stash['pager_url'] = Sher_Core_Helper_Url::build_url_path('app.url.tag', $tag, 'p#p#');
		
		if($this->stash['t'] == 1){
			$this->set_target_css_state('product');
		}elseif($this->stash['t'] == 2){
			$this->set_target_css_state('topic');
		}
		
		$words = Sher_Core_Service_Search::instance()->check_query_string($tag);
		
    	return $this->_display_search_list($words);
    }
	
	/**
     * 搜索标签
     */
    public function search_tag(){
        $this->stash['index_name'] = 'tags';
    	$tag = $this->stash['q'];
        // 若无明细的说明，默认标明此搜索来自标签搜索,以便后期分析能否区别搜索
    	if (empty($this->stash['ref'])) {
           $this->stash['ref'] = 'tag';
    	}
    	if (!empty($tag)) {
           $tag = array($tag);
    	}
        
        return $this->_display_search_list($tag);
    }
    
    /**
     * 标签列表
     */
    public function tags(){
        return $this->display_tab_page('tab_tag','page/index_tag.html');
    }
    /**
     * 热门标签列表
     */
    public function hot_tags(){
    	$tag = new Lgk_Core_Model_Tags();
    	$tags = $tag->get_hot_tags();
    	$this->stash['tags'] = $tags;
    	return $this->display_tab_page('tab_hot', 'page/index_tag.html');
    }

  /**
   * 删除索引-需要管理员权限
   */
  public function del(){
    $id = $this->stash['id'];
    if(empty($id)){
      return $this->ajax_note('参数为空!', true);
    }
    if(!$this->visitor->can_admin){
      return $this->ajax_note('没有权限!', true);   
    }
    $result = Sher_Core_Util_XunSearch::del_ids($id);
    if($result['success']){
      return $this->to_taconite_page('ajax/del_ok.html');   
    }else{
      return $this->ajax_note($result['msg'], true);   
    }

  }

    /**
     * ajax加载更多
     */
    public function ajax_fetch_more(){
    
        $db = $this->stash['db'];
        // 全文搜索/标签搜索
        $evt = isset($this->stash['evt']) ? $this->stash['evt'] : 'content';
        $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
        $t = isset($this->stash['t']) ? (int)$this->stash['t'] : 1;
        $q = isset($this->stash['q']) ? $this->stash['q'] : null;

        if(empty($q)){
            return $this->ajax_json('is empty!', false, '', array('show_type'=>1));
        }

        if($evt=='tag'){
          $this->stash['evt_s'] = '标签';
        }else{
          $this->stash['evt_s'] = '内容';  
        }

        // 过滤xss攻击
        $q = Sher_Core_Helper_FilterFields::remove_xss($q);

        $options = array(
          'page' => $this->stash['page'],
          'size' => $this->stash['size'],
          'evt'  => $evt,
          'sort' => $sort,
          'asc'  => $this->stash['asc'],
          't'    => $t,
        );
        
        $result = Sher_Core_Util_XunSearch::search($q, $options, $db);
        if(!$result['success']){
            return $this->ajax_json($result['msg'], true);
        }

        if(empty($result['data'])){
            return $this->ajax_json('搜索内容为空!', false, '', array('show_type'=>2));
        }

        //  记录当前用户搜索记录
        if($this->visitor->id){
            Sher_Core_Helper_Util::record_user_tag($this->visitor->id, $q, 'search_tags');
        }

        $asset_model = new Sher_Core_Model_Asset();
        $product_model = new Sher_Core_Model_Product();

        $result['show_type'] = 0;
        foreach($result['data'] as $k=>$v){

            $result['data'][$k]['isProduct'] = $result['data'][$k]['isTopic'] = false;

            switch($v['kind']){
                case 'Product':
                    $result['data'][$k]['isProduct'] = true;
                    $product = $product_model->extend_load((int)$v['oid']);
                    if(empty($product)) continue;
                    $row = array();
                    $row['_id'] = $product['_id'];
                    $row['title'] = $product['title'];
                    $row['short_title'] = $product['short_title'];
                    $row['sale_price'] = $product['sale_price'];
                    $row['view_url'] = $product['view_url'];
                    $row['wap_view_url'] = $product['wap_view_url'];
                    $row['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
                    $row['is_product'] = $product['stage']==9 ? true : false;

                    $result['data'][$k]['product'] = $row;
                    break;

                default:
                    
            }

        } // endfor

        return $this->ajax_json('success', false, '', $result);

    }

    
}

