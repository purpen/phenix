<?php
/**
 * 高级搜索
 * @author purpen
 */
class Sher_Wap_Action_Search extends Sher_Wap_Action_Base {
    
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

	protected $exclude_method_list = array('execute', 'xc');
	
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
            $result['data'][$k]['view_url'] = Sher_Core_Helper_Url::wap_stuff_view_url($v['oid']);
            break;
          case 'Topic':
            $result['data'][$k]['view_url'] = sprintf(Doggy_Config::$vars['app.url.wap.social.show'], $v['oid'], 0);
            break;
          case 'Product':
            $result['data'][$k]['view_url'] = sprintf(Doggy_Config::$vars['app.url.wap.shop.view'], $v['oid']);
            break;
          default:
            $result['data'][$k]['view_url'] = '#';
        }

        //封面图---手机上暂不调用图片
        if($v['cover_id']){
          //$result['data'][$k]['asset'] = $asset_model->extend_load($v['cover_id']);
          $result['data'][$k]['asset'] = '';
        }

        // 获取对象属性
        $result['data'][$k]['kind_name'] = Sher_Core_Helper_Search::kind_name($v['kind'], $v['cid']);

        // 获取asset_type
        //$result['data'][$k]['asset_type'] = Sher_Core_Helper_Search::gen_asset_type($v['kind']);

      }

      $pager_url = sprintf('%s/search?t=%d&q=%s&evt=%s&size=%d&sort=%d&page=#p#', Doggy_Config::$vars['app.url.domain'], $this->stash['t'], $this->stash['q'], $this->stash['evt'], $this->stash['size'], $this->stash['s']);
      
      $this->stash['pager_url'] = $pager_url;
      $this->stash['result'] = $result;

    }else{
      
    }

		return $this->to_html_page('wap/search.html');
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
        
		return $this->to_html_page('wap/search.html');
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

    
}
