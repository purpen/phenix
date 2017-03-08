<?php
/**
 * 情境
 * @author tianshuai
 */
class Sher_Wap_Action_Sight extends Sher_Wap_Action_Base {
	
	public $stash = array(
		'id'   => '',
		'page' => 1,
        'size' => 8,

	);
	
	protected $exclude_method_list = array('execute', 'getlist', 'view');

	/**
	 * 情境专题入口
	 */
	public function execute(){
		return $this->getlist();
	}

    /**
     * 列表
     */
    public function getlist(){
        $redirect_url = sprintf("%s/shop", Doggy_Config::$vars['app.url.wap']);
        return $this->to_redirect($redirect_url);
    }

    /**
      *详情
    */
    public function view(){
        $this->set_target_css_state('page_choice');
        $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        $redirect_url = sprintf("%s/shop", Doggy_Config::$vars['app.url.wap']);
        if(empty($id)){
          return $this->show_message_page('访问的情境不存在！', $redirect_url);
        }
        $user_id = $this->visitor->id;

		$model = new Sher_Core_Model_SceneSight();
		$sight = $model->extend_load($id);

		if(empty($sight)) {
            return $this->show_message_page('访问的情境不存在！', $redirect_url);
		}
		if($sight['deleted']==1){
            return $this->show_message_page('访问的情境已删除！', $redirect_url);
		}

		if($sight['is_check']==0){
            return $this->show_message_page('访问的情境未发布！', $redirect_url);
		}

        if(!empty($sight['tags']) && count($sight['tags'])>4){
            $sight['tags'] = array_slice($sight['tags'], 0, 4);
        }

        // 产品
        $products = array();
        if(!empty($sight['product'])){
            $product_model = new Sher_Core_Model_Product();
            for($i=0;$i<count($sight['product']);$i++){
                $p = array();
                $product_id = (int)$sight['product'][$i]['id'];
                $product = $product_model->extend_load($product_id);
                $sight['product'][$i]['price'] = 0;
                if($product){
                    $p['_id'] = $product['_id'];
                    $p['title'] = $product['title'];
                    $p['short_title'] = $product['short_title'];
                    $p['sale_price'] = $product['sale_price'];
                    $p['market_price'] = $product['market_price'];
                    $p['cover_url'] = $product['cover']['thumbnails']['apc']['view_url'];
                    $p['wap_view_url'] = $product['wap_view_url'];
                    $p['category_ids'] = $product['category_ids'];
                    $sight['product'][$i]['price'] = $product['sale_price'];

                    array_push($products, $p);
                }
            } // endfor
            
        }
        $sight['products'] = $products;

		//微信分享
        $wx_share = Sher_Core_Helper_Util::wechat_share_param();
        $this->stash['wx_share'] = $wx_share;

        $this->stash['sight'] = $sight;


        // 记录上一步来源地址
        $this->stash['back_url'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $redirect_url;

  	    return $this->to_html_page('wap/sight/view.html');
    }


	
}
