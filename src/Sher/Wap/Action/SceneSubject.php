<?php
/**
 * 情境专题
 * @author tianshuai
 */
class Sher_Wap_Action_SceneSubject extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'id'   => '',
		'page' => 1,
    'size' => 8,

	);
	
	protected $exclude_method_list = array('execute', 'getlist', 'view');

	/**
	 * 产品专题入口
	 */
	public function execute(){
		return $this->getlist();
	}

    /**
      *详情
    */
    public function view(){
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        $redirect_url = Doggy_Config::$vars['app.url.wap'];
        if(empty($id)){
          return $this->show_message_page('访问的专题不存在！', $redirect_url);
        }
        $user_id = $this->visitor->id;

		$model = new Sher_Core_Model_SceneSubject();
		$scene_subject = $model->load($id);

		if(empty($scene_subject)) {
            return $this->show_message_page('访问的专题不存在！', $redirect_url);
		}

		if($scene_subject['publish']==0){
            return $this->show_message_page('访问的专题未发布！', $redirect_url);
		}

		if($scene_subject['status']==0){
            return $this->show_message_page('访问的专题已禁用！', $redirect_url);
		}

        $product = null;
        if(!empty($scene_subject['product_id'])){
            $product_model = new Sher_Core_Model_Product();
            $row = $product_model->load((int)$scene_subject['product_id']);
            if(!empty($row)){
                $product['_id'] = $row['_id'];
            }
        }
        $scene_subject['product'] = $product;

        // 产品
        $product_arr = array();
        if(!empty($scene_subject['product_ids'])){
            $product_model = new Sher_Core_Model_Product();
            for($i=0;$i<count($scene_subject['product_ids']);$i++){
                $product = $product_model->extend_load($scene_subject['product_ids'][$i]);
                if(empty($product) || $product['deleted']==1 || $product['published']==0) continue;
                $row = array(
                    '_id' => $product['_id'],
                    'title' => $product['short_title'],
                    'banner_url' => $product['banner']['thumbnails']['aub']['view_url'],
                    'summary' => $product['summary'],
                    'market_price' => $product['market_price'],
                    'sale_price' => $product['sale_price'],
                );
                array_push($product_arr, $row);
            }
        }
        $scene_subject['products'] = $product_arr;

        $this->stash['scene_subject'] = $scene_subject;

        if($scene_subject['type']==1){  // 文章
            $tpl = 'wap/scene_subject/artile.html';
        }elseif($scene_subject['type']==3){ // 促销
            $tpl = 'wap/scene_subject/hot.html';       
        }elseif($scene_subject['type']==4){ // 新品
            $tpl = 'wap/scene_subject/new.html';
        }elseif($scene_subject['type']==5){ // 好货(与促销相同)
            $tpl = 'wap/scene_subject/hot.html';            
        }else{
            $tpl = 'wap/scene_subject/artile.html';       
        }
  	    return $this->to_html_page($tpl);
    }

	
}
