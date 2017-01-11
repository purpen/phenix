<?php
/**
 * 分类WAPI接口
 * @author tianshuai
 */
class Sher_WApi_Action_Category extends Sher_WApi_Action_Base {
	
	protected $filter_user_method_list = array('execute', 'getlist');
	
	/**
	 * 入口
	 */
	public function execute(){
		return $this->getlist();
	}
	
	/**
	 * 分类
	 */
	public function getlist(){
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:8;
		$domain = isset($this->stash['domain'])?(int)$this->stash['domain']:1;
		$show_all = isset($this->stash['show_all'])?(int)$this->stash['show_all']:0;
		$show_sub = isset($this->stash['show_sub'])?(int)$this->stash['show_sub']:0;
		$pid = isset($this->stash['pid'])?(int)$this->stash['pid']:0;
		$use_cache = isset($this->stash['use_cache']) ? (int)$this->stash['use_cache'] : 1;
		
		$query   = array();
		$options = array();
        $result = array();
		
		$query['domain'] = $domain;
		$query['is_open'] = Sher_Core_Model_Category::IS_OPENED;

        // 只显示可购买商品的分类
        if($domain==1){
          $query['sub_count'] = array('$ne'=>0);
        }

        if($pid){
            if($pid==-1){
                $query['pid'] = 0;           
            }else{
                $query['pid'] = $pid;
            }
        }
		
        $options['page'] = $page;
        $options['size'] = $size;
        $options['sort_field'] = 'orby';

        $some_fields = array(
            '_id'=>1, 'title'=>1, 'name'=>1, 'gid'=>1, 'pid'=>1, 'order_by'=>1, 'sub_count'=>1, 'tag_id'=>1,
            'domain'=>1, 'is_open'=>1, 'total_count'=>1, 'reply_count'=>1, 'state'=>1, 'app_cover_url'=>1,
            'tags'=>1, 'back_url'=>1, 'stick'=>1,
        );
		
        $options['some_fields'] = $some_fields;

        // 从redis获取 
        if($use_cache){
            $r_key = sprintf("w_api:category:%s_%s_%s_%s_%s_%s", $domain, $show_all, $show_sub, $pid, $page, $size);
            $redis = new Sher_Core_Cache_Redis();
            $result = $redis->get($r_key);
            if($result){
                return $this->wapi_json('请求成功', 0, json_decode($result, true));
            }       
        }

        $category_model = new Sher_Core_Model_Category();
        $service = Sher_Core_Service_Category::instance();
        $result = $service->get_category_list($query, $options);

        // 过滤多余属性
        $filter_fields = array('view_url', 'state', 'is_open', '__extend__');
        $data = array();
        for($i=0;$i<count($result['rows']);$i++){
          foreach($options['some_fields'] as $key=>$value){
            $data[$i][$key] = isset($result['rows'][$i][$key]) ? $result['rows'][$i][$key] : 0;
          }

          // banner图url
          $data[$i]['app_cover_url'] = null;
          if(isset($result['rows'][$i]['app_cover_url']) && !empty($result['rows'][$i]['app_cover_url'])){
            $data[$i]['app_cover_url'] = $result['rows'][$i]['app_cover_url'];
            $data[$i]['app_cover_s_url'] = sprintf("%s-p325x200.jpg", $result['rows'][$i]['app_cover_url']);
          }
          $sub_categories = array();
          // 加载子类
          if($show_sub){
            $sub_categories = $category_model->find(array('pid'=>$data[$i]['_id'], 'is_open'=>1));
          }
          $data[$i]['sub_categories'] = $sub_categories;

        }

        // 显示全部
        if($show_all){
            if($domain==10){
                $title_all = '全部好货';
            }else{
                $title_all = '全部';
            }
          $arr = array(
            '_id' => 0,
            'title' => $title_all,
            'name' => 'all',
            'tag_id' => 0,
            'domain' => $domain,
            //'app_cover_url' => 'http://frbird.qiniudn.com/asset/160607/5756b69dfc8b12a1478ba705-1-hu.jpg',
            'app_cover_url' => 'http://frbird.qiniudn.com/asset/160823/57bbaf7cfc8b1283608bcf87-7-hu.jpg',
            //'back_url' => 'http://frbird.qiniudn.com/asset/160707/577e1b74fc8b12b31c8b6de5-10-hu.jpg',
            'back_url' => 'http://frbird.qiniudn.com/asset/160711/5783332afc8b12b11c8ba757-10-hu.jpg',
            'order_by' => 0,
            'tags' => array(),
          );
          array_unshift($data, $arr);
        }

		$result['rows'] = $data;
        $result['rows'] = Sher_Core_Helper_FilterFields::filter_fields($result['rows'], $filter_fields, 2);

        if($use_cache && !empty($result)){
            $redis->set($r_key, json_encode($result), 3600);
        }

		return $this->wapi_json('请求成功', 0, $result);
	}
}

