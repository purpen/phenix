<?php
/**
 * 产品列表标签
 * @author purpen
 */
class Sher_App_ViewTag_ProductList extends Doggy_Dt_Tag {
    
	protected $argstring;
    public function __construct($argstring, $parser, $pos = 0) {
        $this->argstring = $argstring;
    }
	
    /**
     * 列表的条件保持与索引顺序一致(non-PHPdoc)
     * @see Doggy/Dt/Doggy_Dt_Node#render()
     */
    public function render($context, $stream) {
        
		$page = 1;
        $size = 10;
		
		// 获取单个产品
		$product_id = 0;
		// 多个产品
		$product_ids = array();
		$sku = 0;
		
		$category_id = 0;
        $user_id = 0;
        $deleted = -1;
		$stage = 0;
        $brand_id = 0;
		
		$process_voted = 0;
		$process_presaled = 0;
		$process_saled = 0;

        // 查询类型
        $type = 0;
		
		$only_approved = 0;
		$only_published = 0;
		$only_onsale = 0;
		$only_stick = 0;
		$is_shop = 0;
        $is_shop_wap = 0;
        // 创意投票或产品灵感
        $is_idea = 0;
        
		$presaled = 0;
		// 搜索类型
		$s_type = 0;
		$s_mark = null;
		// 是否成功案例
		$only_okcase = 0;
        // 是否可推广
        $is_commision = 0;
		
		// 是否有话题
		$only_subject = 0;
		
		$sort = 'latest';

		// 投票状态显示
		$vote_type = 0;
        // 是否开普勒 
        $is_vop = 0;
        // 价格区音
        $min_price = 0;
        $max_price = 0;

    // 孵化产品标识
    $hatched = 0;
		
        $var = 'list';
        $include_pager = 0;
        $pager_var = 'pager';
		
        $ttl = 900;
        $endmid = null;
		
        extract($this->resolve_args($context,$this->argstring,EXTR_IF_EXISTS));
		
        $page = (int) $page;
        $page || $page = 1;
        $size = (int)$size;
		
        $query = array();
		
		// 获取单个产品
		if ($product_id) {
			$result = DoggyX_Model_Mapper::load_model((int)$product_id, 'Sher_Core_Model_Product');
			$context->set($var, $result);
			return;
		}
		
		// 获取一组产品列表
		if (!empty($product_ids)){
			$result = DoggyX_Model_Mapper::load_model_list($product_ids, 'Sher_Core_Model_Product');
			$context->set($var, $result);
			return;
		}
		
		// 获取单个产品
		if ($sku) {
            $product = new Sher_Core_Model_Product();
            $result = $product->find_by_sku($sku);
			
			$context->set($var, $result);
			return;
		}

        // 品牌
        if($brand_id){
            $query['brand_id'] = $brand_id;
        }
		
        if ($user_id) {
            if(is_array($user_id)){
                $query['designer_id'] = array('$in'=>$user_id);
            }else{
                $query['user_id'] = (int)$user_id;
            }
        }
		
		if ($category_id) {
			$query['category_ids'] = (int)$category_id;
		}
		
		if ($stage) {
			$query['stage'] = (int)$stage;
		}

        // 除了投票
        if ($is_shop) {
            $query['stage'] = array('$in'=>array(5, 9, 12, 15));
        }

        // wap端显示:预售和商品
        if ($is_shop_wap){
            $query['stage'] = array('$in'=>array(5,9));
        }
        // 投票或灵感(个人中心用)
        if ($is_idea){
            $query['stage'] = array('$in'=>array(1, 15));
        }

        // vop
        if($is_vop){
            if((int)$is_vop==-1){
                $query['is_vop'] = 0;
            }else{
                $query['is_vop'] = 1;
            }
        }

        // 是否可推广
        if($is_commision){
            if((int)$is_commision==-1){
                $query['is_commision'] = 0;
            }else{
                $query['is_commision'] = 1;
            }
        }

		//预售
		if($presaled){
		  $query['stage'] = 5;
		}
		
		if($process_saled){
			$query['process_saled'] = 1;
		}
		if($process_presaled){
			$query['process_presaled'] = 1;
		}
		if($process_voted){
			$query['process_voted'] = 1;
		}
		
		if ($only_approved) {
			$query['approved'] = 1;
		}
		
		if ($only_onsale) {
			$query['published'] = 1;
		}
		
		if ($only_stick){
			$query['stick'] = 1;
		}
		
		if ($only_subject){
			$query['topic_count'] = array('$gt'=>0);
		}

        if($min_price && $max_price){
            $query['sale_price'] = array('$gt'=>(float)$min_price, '$lt'=>(float)$max_price);
        }elseif($min_price){
            $query['sale_price'] = array('$gt'=>(float)$min_price);       
        }elseif($max_price){
            $query['sale_price'] = array('$lt'=>(float)$max_price);       
        }
		
		// 是否成功案例
		if($only_okcase){
			$query['okcase'] = 1;
		}
    // 是否是孵化产品
    if($hatched){
      $query['hatched'] = 1;
    }

		// 投票状态显示
		if($vote_type){
		  if((int)$vote_type==2){
			// 进行中的投票
			$query['voted_finish_time'] = array('$gt'=>time());
		  }
		}

        if($type){
            switch((int)$type){
                case 1:
                    $query['stage'] = 15;
                    break;
                case 2:
                    $query['stage'] = array('$in'=>array(5,9));
                    break;
                case 3:
                    $query['stage'] = 12;
                    break;
                case 4:
                    $query['stick'] = 1;
                    break;
                case 5:
                    $query['featured'] = 1;
                    break;
                case 6:
                    $query['snatched'] = 1;
                    break;
                case 7:
                    $query['stage'] = 5;
                    break;
            }
        }
		
	    // 搜索
	    if($s_type && $s_mark){
	      switch ((int)$s_type){
		      case 1:
		        $query['_id'] = (int)$s_mark;
		        break;
		      case 2:
		        $query['title'] = array('$regex'=>$s_mark);
		        break;
		      case 3:
		        $query['tags'] = array('$all'=>array($s_mark));
		        break;
	      }
	    }

        // 是否删除
        if($deleted){
            if((int)$deleted==-1){
                $query['deleted'] = 0;
            }elseif((int)$deleted==1){
                $query['deleted'] = 1;
            }
        }
		
        $service = Sher_Core_Service_Product::instance();
        $options['page'] = $page;
        $options['size'] = $size;
		$options['sort_field'] = $sort;

		// 排序
		switch ((int)$sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'vote';
				break;
			case 2:
				$options['sort_field'] = 'love';
				break;
			case 3:
				$options['sort_field'] = 'comment';
				break;
			case 4:
				$options['sort_field'] = 'stick:update';
				break;
			case 5:
				$options['sort_field'] = 'featured:update';
				break;
			case 6:
				$options['sort_field'] = 'price';
				break;
			case 7:
				$options['sort_field'] = 'price_asc';
				break;
			case 8:
				$options['sort_field'] = 'commision_desc';
				break;
		}
		
        $result = $service->get_product_list($query, $options);
        $context->set($var,$result);
        if ($include_pager) {
            $context->set($pager_var,$result['pager']);
        }
    }
}
