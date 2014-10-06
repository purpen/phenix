<?php
class Sher_App_Action_Search extends Sher_App_Action_Base {
    public $stash = array(
		'page'=>1,
		'q'=>'',
		'ref'=>'',
		't'=>2,
		'index_name'=>'full',
	);
	
	public function execute() {
       	$words = Sher_Core_Service_Search::instance()->check_query_string($this->stash['q']);
        return $this->_display_search_list($words);
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
		}
        
		return $this->to_html_page('page/search.html');
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
    
}
?>