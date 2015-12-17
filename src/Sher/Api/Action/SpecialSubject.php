<?php
/**
 * API 接口
 * 商品专题
 * @author tianshuai
 */
class Sher_Api_Action_SpecialSubject extends Sher_Api_Action_Base implements Sher_Core_Action_Funnel {

	protected $exclude_method_list = array('execute');
	
	/**
	 * 入口
	 */
	public function execute(){
		
		return $this->getlist();
	}
	
	/**
	 * 专题列表
	 */
	public function getlist(){
		
		$page = isset($this->stash['page'])?(int)$this->stash['page']:1;
		$size = isset($this->stash['size'])?(int)$this->stash['size']:10;
		
		$some_fields = array(
			'_id'=>1, 'title'=>1, 'category_id'=>1, 'content'=>1, 'products'=>1, 'banner_id'=>1,
			'cover_id'=>1, 'tags'=>1, 'summary'=>1, 'user_id'=>1, 'kind'=>1, 'stick'=>1,
			'state'=>1, 'view_count'=>1, 'comment_count'=>1, 'love_count'=>1, 'favorite_count'=>1,
		);
		
		// 请求参数
		$category_id = isset($this->stash['category_id']) ? (int)$this->stash['category_id'] : 0;
		$user_id  = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
		$stick = isset($this->stash['stick']) ? (int)$this->stash['stick'] : 0;
		$sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;
			
		$query   = array();
		$options = array();
		
		// 查询条件
		if($category_id){
			$query['category_id'] = (int)$category_id;
		}
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		
		if($stick){
			if($stick==-1){
					$query['stick'] = 0;
			}else{
					$query['stick'] = $stick;
			}
		}
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;

		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'stick:latest';
				break;
		}
		
		$options['some_fields'] = $some_fields;
		// 开启查询
		$service = Sher_Core_Service_SpecialSubject::instance();
		$result = $service->get_special_subject_list($query, $options);
		
		// 重建数据结果
		for($i=0;$i<count($result['rows']);$i++){
			// 封面图url
			$result[$i]['cover_url'] = $result['rows'][$i]['cover']['thumbnails']['medium']['view_url'];
		}
		
		return $this->api_json('请求成功', 0, $result);
	}
	
	/**
	 * 详情
	 */
	public function view(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->api_json('访问的专题不存在！', 3000);
		}
		
		$model = new Sher_Core_Model_SpecialSubject();
		$special_subject = $model->load((int)$id);

		if($special_subject['state']==0){
			return $this->api_json('访问的专题已禁用！', 3001);
		}

		if(empty($special_subject)) {
				return $this->api_json('访问的专题不存在！', 3002);
		}
		$special_subject = $model->extended_model_row($special_subject);
		$special_subject['content'] = null;
		$product_arr = array();
		
		if($special_subject['kind']==Sher_Core_Model_SpecialSubject::KIND_APPOINT){
			if(!empty($special_subject['product_ids'])){
			  $product_model = new Sher_Core_Service_Product();
			  foreach($special_subject['product_ids'] as $k=>$v){
				$product = $product_model->extend_load((int)$v);
				if(!empty($product)){
				  array_push($product_arr, $product);
				}
			  } // endfor
			} // endif empty
			$special_subject['products'] = $product_arr;
		} // endif kind
		
		if($special_subject['kind']==Sher_Core_Model_SpecialSubject::KIND_CUSTOM){
			$product['content_view_url'] = sprintf('%s/app/api/view/special_subject_show?id=%d', Doggy_Config::$vars['app.domain.base'], $special_subject['_id']);
		} // endif kind
		
		// 增加pv++
		$model->inc_counter('view_count', 1, (int)$id);

		return $this->api_json('请求成功', 0, $special_subject);
	}




	
}

