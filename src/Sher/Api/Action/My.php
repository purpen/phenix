<?php
/**
 * API 接口
 * @author purpen
 */
class Sher_Api_Action_My extends Sher_Core_Action_Authorize {
	public $stash = array(
		'page' => 1,
		'size' => 5,
		'type' => 1,
	);

	/**
	 * 入口
	 */
	public function execute() {
		
	}
	
	/**
	 * 我的收藏
	 */
	public function favorite(){
		$page = $this->stash['page'];
		$size = $this->stash['size'];
		
		$type = (int)$this->stash['type'];
		$user_id = (int)$this->stash['user_id'];
		if(!in_array($type, array(1,2))){
			return $this->api_json('请求参数不匹配！', 3000);
		}
		if(empty($user_id)){
			return $this->api_json('缺少请求参数！', 3000);
		}
		
		if($type == 1){
			$some_fields = array(
				'_id'=>1, 'title'=>1, 'advantage'=>1, 'sale_price'=>1, 'market_price'=>1, 'presale_people'=>1,
				'presale_percent'=>1, 'cover_id'=>1, 'designer_id'=>1, 'category_id'=>1, 'stage'=>1, 'vote_favor_count'=>1,
				'vote_oppose_count'=>1, 'summary'=>1, 'succeed'=>1, 'voted_finish_time'=>1, 'presale_finish_time'=>1,
				'snatched_time'=>1, 'inventory'=>1, 'can_saled'=>1, 'topic_count'=>1,'presale_money'=>1,
			);
		}else{
			$some_fields = array(
				'_id'=>1, 'title'=>1, 'created_on'=>1, 'comment_count'=>1,'user_id'=>1,
			);
		}
		
		$query = array();
		$options = array();
		
		// 查询条件
		if($user_id){
			$query['user_id'] = (int)$user_id;
		}
		if($type){
			$query['type'] = (int)$type;
		}
		$query['event'] = Sher_Core_Model_Favorite::EVENT_FAVORITE;
		
		// 分页参数
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = 'time';
		
		// 开启查询
        $service = Sher_Core_Service_Favorite::instance();
        $result = $service->get_like_list($query, $options);
		
		// 重建数据结果
		$data = array();
		for($i=0;$i<count($result['rows']);$i++){
			if($type == 1){
				foreach($some_fields as $key=>$value){
					$data[$i][$key] = $result['rows'][$i]['product'][$key];
				}
				$product = new Sher_Core_Model_Product();
				// 获取商品价格区间
				$data[$i]['range_price'] = $product->range_price($result['rows'][$i]['product']['_id'], $result['rows'][$i]['product']['stage']);
				
				// 封面图url
				$data[$i]['cover_url'] = $result['rows'][$i]['product']['cover']['thumbnails']['medium']['view_url'];
			}
			if($type == 2){
				foreach($some_fields as $key=>$value){
					$data[$i][$key] = $result['rows'][$i]['topic'][$key];
				}
			}
		}
		$result['rows'] = $data;
		
		return $this->api_json('请求成功', 0, $result);
	}
	
}
?>