<?php
/**
 * api show
 * @author tianshuai
 */
class Sher_Api_Action_View extends Sher_App_Action_Base {
	
	public $stash = array(
		'id' => 0,
	);
	
	protected $exclude_method_list = array('topic_show', 'product_show', 'execute');

	
	/**
	 * api show
	 */
	public function execute(){
		//return $this->index();
	}
	
	/**
	 * 显示主题详情帖---手机app content
	 */
	public function topic_show(){
		$id = (int)$this->stash['id'];
		
		$redirect_url = Doggy_Config::$vars['app.url.topic'];
		if(empty($id)){
			return $this->api_json('访问的主题不存在或已被删除！', 3001);
		}
		
		$model = new Sher_Core_Model_Topic();
		$topic = $model->load($id);
		
		if(empty($topic) || $topic['deleted']){
			return $this->api_json('访问的主题不存在或已被删除！', 3002);
		}

    //创建关联数据
    $topic = $model->extended_model_row($topic);
		
		$this->stash['topic'] = &$topic;
		
		return $this->to_html_page('page/topic/api_show.html');
	}

	/**
	 * app商品描述部分html5展示
	 */
	public function product_show(){
		$id = (int)$this->stash['id'];
		if(empty($id)){
			return $this->api_json('访问的产品不存在！', 3000);
		}
		
		$product = array();
		
		$model = new Sher_Core_Model_Product();
		$product = $model->load((int)$id);

		if($product['deleted']){
			return $this->api_json('访问的产品不存在或已被删除！', 3001);
		}

    //加载model扩展数据
    $product = $model->extended_model_row($product);

		$this->stash['product'] = &$product;
		return $this->to_html_page('page/product/api_show.html');
	}

}
?>
