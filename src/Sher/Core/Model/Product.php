<?php
/**
 * 产品Model
 * @author purpen
 */
class Sher_Core_Model_Product extends Sher_Core_Model_Base {

    protected $collection = "product";
	protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;
	
	# 产品周期stage
    const STAGE_VOTE     = 1;
    const STAGE_PRESALE  = 5;
    const STAGE_SHOP     = 9;
    const STAGE_EXCHANGE = 12;
    const STAGE_IDEA     = 15;
    const STAGE_SCENE    = 16;
	
    protected $schema = array(
		'_id'     => null,
        # 只是记录导过来的stuff_id
        'old_stuff_id' => null,
		# taobao sku
		'taobao_iid' => null,

        # 是否是京东产品
        'is_vop' => 0,
        # 站外编号(erp编号)
        'number' => null,
		# 产品名称
		'title'   => '',
		# 短标题
		'short_title' => '',
		# 优势/亮点
		'advantage' => '',
		# 简述
		'summary' => '',
		# 详情内容
        'content' => '',
        # 手机详情
        'content_wap' => '',
		# 产品标签
		'tags'    => array(),
        'like_tags' => array(),
		
		# 产品视频链接
		'video' => array(),
		
		# 访问地址
		'view_url' => '',

        # 额外优惠信息
        'extra_info' => '',
        # 服务信息
        'extra' => array(
            'server' => '',
            'tag' => '',
        ),
		
		# 封面图
		'cover_id' => '',
        # Banner_id
        'banner_id' => '',
		'asset' => array(),
		# 附件图片数
        'asset_count' => 0,
        # 去底图
        'png_asset_ids' => array(),
		
		# 类别
		'category_id' => 0,
        # 类别支持多选(new)
        'category_ids' => array(),
        # 分类标签
        'category_tags' => array(),
        # 所属父ID(用于3C数码父类:app_category_id)
        'pid' => 0,
        # 3C类别
        'app_category_id' => 0,

        # 场景
        'scene_ids' => array(),
        # 风格
        'style_ids' => array(),
		
		# 上传者
		'user_id' => null,
		# 设计者/团队
		'designer_id' => 0,

        # 品牌ID
        'cooperate_id' => null,
        # 品牌ID -- 用于app商城
        'brand_id' => '',

        # 关联产品ID
        'fever_id' => 0,
		
		## 价格
		
		# 成本价
		'cost_price'   => 0,
		# 市场价
		'market_price' => 0,
		# 销售价
		'sale_price'   => 0,
		# 预售价
		'hot_price'    => 0,
		
		# 库存数量
		'inventory'  => 0,
		# 销售数
		'sale_count' => 0,
		
		# 同步数量,单个sku情况
		'sync_count' => 0,
		
		# 商品属性信息
		'attributes' => array(
			'width'  => 0,
			'height' => 0,
			'weight' => 0,
			'color'  => 0,
		),
		
		# 其他扩展信息
		'meta' => array(
			# 商品单位
			'unit' => null,
		),
		
		// 商店sku数量
		'sku_count' => 0,
		
		# 商品型号、颜色
		/**
		 * array(
		 *    'r_id'     => 0,
		 *    'name'     => '',
		 *    'quantity' => '',
		 *    'price'    => 0,
		 *    # 已售数量
		 *    'sold'     => 0,
		 * )
		 */
		// 'mode' => array(),
		
		## 预售信息设置
		
		# 预售项数
		'mode_count' => 0,
		/*
		 * array(
		 *    'r_id'     => 0,
		 *	  'name'     => '',
		 *    'summary'  => '',
		 *    'mode'     => '',
		 *    'quantity' => 0,
		 *    'price'    => 0,
		 *    # 已售数量
		 *    'sold'     => 0,
		 * ),
		 */
		// 'presales' => array(),
		# 预售库存数量
		'presale_inventory' => 0,
		'presale_count' => 0,
		'presale_people' => 0,
		'presale_money' => 0,
		
		# 预售目标金额
		'presale_goals' => 0,
		
		# 预售开始时间
		'presale_start_time'  => null,
		# 预售完成时间
		'presale_finish_time' => null,
		
		## 时间点
		
		# 投票开始时间
		'voted_start_time'    => null,
		# 投票结束时间
		'voted_finish_time'   => null,
		
		## 限时抢购
		'snatched' => 0,
        # 抢购开始结束时间
		'snatched_time' => 0,
        'snatched_end_time' => 0,
		# 预约人数
		'appoint_count' => 0,
		# 抢购价
		'snatched_price' => 0,
		# 抢购数量
		'snatched_count' => 0,

		## APP限时抢购
		'app_snatched' => 0,
        # 抢购开始结束时间
		'app_snatched_time' => 0,
        'app_snatched_end_time' => 0,
		# 提醒人数
		'app_appoint_count' => 0,
		# 抢购价
		'app_snatched_price' => 0,
		# 抢购数量
		'app_snatched_count' => 0,
        # 抢购总数量
        'app_snatched_total_count' => 0,
        # 展示图
        'app_snatched_img' => null,
        # 限购购买数量
        'app_snatched_limit_count' => 0,

		## 试用
		'trial' =>  0,
        ## 3C类
        'is_app_category' => 0,

		## 积分设置
		'exchanged' => 0,
		# 所需最高鸟币数量
		'max_bird_coin' => 0,
		# 所需最低鸟币数
		'min_bird_coin' => 0,
		# 补价格
		'exchange_price' => 0,
		# 兑换数量
		'exchange_count' => 0,
		
		## 计数器
		
		# 浏览数
		'view_count'=>0,
		# 收藏数
		'favorite_count' => 0, 
		# 喜欢数
		'love_count' => 0,
        # 虚拟喜欢数量
        'invented_love_count' => 0,
		# 回应数 
		'comment_count' => 0,
		# 评价星数
		'comment_star' => 0,
		# 话题数
		'topic_count' => 0,
		# 赞成数
		'vote_favor_count' => 0,
		# 反对数
		'vote_oppose_count' => 0,
		# 相关灵感数
		'stuff_count' => 0,

        # 真实浏览数
        'true_view_count' => 0,
        # web 浏览数
        'web_view_count' => 0,
        # wap 浏览数 
        'wap_view_count' => 0,
        # app 浏览数
        'app_view_count' => 0,
		
		## 专家评分
		
		# 评分人数
		'score_count' => 0,
		# 综合平均分
		'score_average' => 0,
		
		# 评分值
		'score' => array(
			# 可用性
			'usability' => 0,
			# 外观设计
			'design' => 0,
			# 创意性
			'creativity' => 0,
			# 功能性
			'content' => 0,
		),
		
		# 产品周期 (投票、预售、销售, 积分兑换, 智品库)
		'stage' => self::STAGE_VOTE,
		
		# 是否在投票列表显示
		'process_voted' => 0,
		# 是否在预售列表显示
		'process_presaled' => 0,
		# 是否在商店列表显示
		'process_saled' => 0,
        # 产品灵感
        'process_idea' => 0,
		
		# 投票申请/产品灵感是否审核
		'approved' => 0,
		# 投票是否成功
		'succeed' => 0,
		# 预售，销售产品是否发布
		'published' => 0,

        # 最后赞用户列表
        'last_love_users' => array(),

        # 商品介绍(从智品库过来数据)
        'product_info' => array(),
		
		# 推荐（编辑推荐、推荐至首页）
		'stick' => 0,
        'stick_on' => 0,
        # 精选
        'featured' => 0,
        'featured_on' => 0,
		# 是否成功案例产品
		'okcase' => 0,

        # 产品所在专辑
        'album_ids' => array(),
		
		# 状态
		'state' => 0,
		# 删除标识
		'deleted' => 0,
		# 随机数
		'random' => 0,
		# 最近一次编辑人ID
		'last_editor_id' => 0,
        # 使用手册关联话题ID
        'guide_id' => 0,

        # 孵化产品标识
        'hatched' => 0,
        # 孵化产品封面
        'hatched_cover_url' => null,
        # 来源: 1.编辑；2.用户；3.--
        'from_to' => 1,
        # 发货类型: 1.自营；2.代发；3.--
        'send_type' => 1,
        # 供应商
        'supplier_id' => null,
        # 是否可推广: 0.否；1.是；
        'is_commision' => 0,
        # 佣金比例
        'commision_percent' => 0,
        # 虚拟或实物: 1.实物；2.虚拟；3.--
        'kind' => 1,

    );
	
	protected $required_fields = array('user_id','title');
	protected $int_fields = array('user_id','designer_id','category_id','inventory','sale_count','presale_count','presale_people', 'mode_count','appoint_count','state','published','deleted','process_voted','process_presaled','process_saled','presale_inventory','snatched_count','app_snatched_count','app_snatched_total_count','stuff_count','last_editor_id','max_bird_coin','min_bird_coin','exchange_count','app_snatched_limit_count','guide_id','hatched', 'app_category_id', 'pid', 'featured_on', 'stick_on', 'from_to', 'number','send_type','is_commision');
	protected $float_fields = array('cost_price', 'market_price', 'sale_price', 'hot_price', 'presale_money', 'presale_goals', 'snatched_price', 'app_snatched_price', 'exchange_price','commision_percent');
	protected $counter_fields = array('inventory','sale_count','presale_count', 'mode_count','asset_count', 'view_count', 'favorite_count', 'love_count', 'comment_count','topic_count','vote_favor_count','vote_oppose_count','appoint_count','stuff_count','exchange_count', 'app_appoint_count', 'true_view_count', 'web_view_count', 'wap_view_count', 'app_view_count');
	protected $retrieve_fields = array('content'=>0);
	protected $joins = array(
	    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
		'designer' => array('designer_id'  => 'Sher_Core_Model_User'),
		'category' => array('category_id' => 'Sher_Core_Model_Category'),
		'brand' => array('brand_id' => 'Sher_Core_Model_SceneBrands'),
	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		if(!isset($row['view_url']) || empty($row['view_url'])){
			$row['view_url'] = $this->gen_view_url($row);
		}
		if(!isset($row['short_title']) || empty($row['short_title'])){
			$row['short_title'] = $row['title'];
		}
		$row['mm_view_url'] = sprintf(Doggy_Config::$vars['app.url.mm_shop.view'], $row['_id']);
		$row['wap_view_url'] = sprintf(Doggy_Config::$vars['app.url.wap.shop.view'], $row['_id']);
		$row['subject_view_url'] = Sher_Core_Helper_Url::product_subject_url($row['_id']);
		$row['vote_view_url'] = Sher_Core_Helper_Url::vote_view_url($row['_id']);
		$row['presale_view_url'] = Sher_Core_Helper_Url::sale_view_url($row['_id']);

        $row['comment_view_url'] = sprintf(Doggy_Config::$vars['app.url.shop'].'/view/%d/%d', $row['_id'], 1);
            
            $row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';

        if(isset($row['category_ids']) && !empty($row['category_ids'])){
              $row['category_ids_s'] = implode(',',$row['category_ids']);   
        }

        if(isset($row['category_tags']) && !empty($row['category_tags'])){
              $row['category_tags_s'] = implode(',',$row['category_tags']);   
        }

        // 场景转换字符串
        if(isset($row['scene_ids']) && !empty($row['scene_ids'])){
              $row['scene_ids_to_s'] = implode(',', $row['scene_ids']);   
        }
        // 风格转换字符串
        if(isset($row['style_ids']) && !empty($row['style_ids'])){
              $row['style_ids_to_s'] = implode(',', $row['style_ids']);   
        }

        // 发货类型
        $row['send_label'] = '';
        if(isset($row['send_type'])){
            switch($row['send_type']){
                case 1:
                    $row['send_label'] = '自营';
                    break;
                case 2:
                    $row['send_label'] = '代发';
                    break;
                default:
                    $row['send_label'] = '--';
            }
        }

        // 封面图
        $row['cover'] = $this->cover($row);
        $row['banner'] = $this->banner($row);


        if(isset($row['vote_favor_count']) && isset($row['vote_oppose_count'])){
            $row['vote_count'] = $row['vote_favor_count'] + $row['vote_oppose_count'];
        }
		
        if($row['stage']){
            if ($row['stage'] == self::STAGE_VOTE){
                $row['stage_label'] = '投票中';
                // 计算投票完成比
                $lowest = Doggy_Config::$vars['app.vote.lowest'];
                $row['vote_percent'] = sprintf("%.2f", $row['vote_favor_count']/$lowest*100);
            }else if ($row['stage'] == self::STAGE_PRESALE){
                $row['stage_label'] = '预售中';
                $row['presale'] = 1;
            }else if ($row['stage'] == self::STAGE_SHOP){
                $row['stage_label'] = '热售中';
                $row['hotsale'] = 1;
            }else if ($row['stage'] == self::STAGE_EXCHANGE){
                $row['stage_label'] = '积分兑换';
                $row['exchange'] = 1;
            }else if ($row['stage'] == self::STAGE_IDEA){
                $row['stage_label'] = '产品灵感';
                $row['idea'] = 1;
            }else if ($row['stage'] == self::STAGE_SCENE){
                $row['stage_label'] = '情境产品';
            }else{
                $row['stage_label'] = '未设置1'; // 未知
            }
        }else{
            $row['stage_label'] = '未设置2'; // 未知  
        }
		
		// HTML 实体转换为字符
		if (isset($row['content'])){
			$row['content'] = htmlspecialchars_decode($row['content']);
		}
		if (isset($row['content_wap'])){
			$row['content_wap'] = htmlspecialchars_decode($row['content_wap']);
		}
		
		// 去除 html/php标签
        if(isset($row['summary']) && !empty($row['summary'])){
		    $row['strip_summary'] = strip_tags(htmlspecialchars_decode($row['summary']));
        }
		
		$this->expert_assess($row);
		
		// 预售项排序,根据r_id正序排列
		if(isset($row['presales'])){
			foreach ($row['presales'] as $key => $value) {
				$rid[$key] = $value['r_id'];
				$price[$key] = $value['price'];
			}
			array_multisort($rid, $price, $row['presales']);
		}
		// 预售百分比
		if(isset($row['presale_goals']) && isset($row['presale_money']) && $row['presale_goals'] != 0){
			$row['presale_percent'] = sprintf("%.2f", $row['presale_money']*100/$row['presale_goals']);
		}else{
			$row['presale_percent'] = 0;
		}
		// 投票是否结束
		$row['voted_finished'] = (isset($row['voted_finish_time']) && $row['voted_finish_time'] < time()) ? true : false;
		
		// 预售是否结束
		$row['presale_finished'] = (isset($row['presale_finish_time']) && $row['presale_finish_time'] < time()) ? true : false;
		
		// 抢购开启
		if(isset($row['snatched_time'])){
			$row['snatched_start'] = ($row['snatched_time'] && ($row['snatched_time'] < time())) ? true : false;
		}
		
		// 检测是否可售
		$row['can_saled'] = $this->can_saled($row);

	    // 是否是试用
	    if(isset($row['trial'])){
	    	$row['is_try'] = $this->is_try($row['trial']);
	    }else{
	    	$row['is_try'] = false;
	    }
        
        // 是否为新品
        $row['newest'] = ($row['stage'] == 5) ? 1 : 0;
        
        // 是否为热门
        if(isset($row['sale_count']) && $row['sale_count'] > 100) {
            $row['hot'] = 1;
        }else{
            $row['hot'] = 0;
        }

        // 列表tips展示
        $row['tips_label'] = 0;

        if(isset($row['featured']) && $row['featured']==1){
          $row['tips_label'] = 2;
        }else{
          $row['tips_label'] = $row['created_on']>(time()-1209600) ? 1 : $row['tips_label'];
        }
        
        if($row['stage'] == self::STAGE_SHOP && isset($row['comment_count']) && $row['comment_count'] > 0){
          if(isset($row['comment_star'])){
            $stars = $row['comment_star']/$row['comment_count'];
            $row['stars'] = ceil($stars);
            // 10分值显示
            $row['stars_value'] = sprintf("%.1f", $stars*2);        
          }
        }

        // 分成百分比转化
        $row['commision_percent_p'] = isset($row['commision_percent']) ? $row['commision_percent']*100 : 0;
        $row['balance_price'] = 0;
        if(isset($row['commision_percent']) && isset($row['sale_price'])){
            $row['balance_price'] = sprintf("%.2f", ($row['commision_percent'] * $row['sale_price']));
        }
	}
	
	/**
	 * 验证是否能够销售
	 */
	public function can_saled($data){
    if(isset($data['inventory'])){
      //验证抢购数量
      if(!empty($data['snatched'])){
        if(isset($data['snatched_count']) && $data['snatched_count']>0 && $data['inventory']>0){
          return true;      
        }else{
          return false;
        }
      }
      //验证兑换数量
      if($data['stage']==self::STAGE_EXCHANGE){
        if(isset($data['exchange_count']) && $data['exchange_count']>0 && $data['inventory']>0){
          return true;
        }else{
          return false;
        }
      }
			return $data['inventory'] > 0;
		}
		return false;
	}

	/**
	 * 验证app端是否能够销售
	 */
	public function app_can_saled($data){
    if(!$this->can_saled($data)){
      return false;
    }
    // 如果是闪购进行中,验证库存
    $app_snatched_stat = $this->app_snatched_stat($data);
    if($app_snatched_stat==2){
      return $data['app_snatched_count'] >= $data['app_snatched_total_count'];
    }

  }

  /**
   * 是否是app闪购
   */
  public function is_app_snatched($data){
    if(isset($data['app_snatched']) && !empty($data['app_snatched'])){
      return 1;
    }
    return 0;
  }

  /**
   * 返回app闪购状态
   * 0.非app闪购；1.未开始；2.进行中；3.已结束
   */
  public function app_snatched_stat($data){
    if($this->is_app_snatched($data)){
      if(!isset($data['app_snatched_time']) || $data['app_snatched_time']<=0){
        return 3;
      }
      if(!isset($data['app_snatched_end_time']) || $data['app_snatched_end_time']<=0){
        return 3;
      }
      $now_time = time();
      if($data['app_snatched_time']>$now_time){
        return 1;
      }elseif($data['app_snatched_time']<=$now_time && $data['app_snatched_end_time']>=$now_time){
        return 2;
      }else{
        return 3;
      }

    }else{
      return 0;
    }
  }

  /**
   * 是否是试用
   */
  public function is_try($trial=0){
    if(empty($trial)){
      return false;
    }
    return true;
  }
	
	/**
	 * 获取产品的价格区间
	 */
	public function range_price($id, $stage){
		$inventory = new Sher_Core_Model_Inventory();
		$rows = $inventory->find(array('product_id'=>(int)$id, 'stage'=>(int)$stage));
		
		$prices = array();
		if(!empty($rows)){
			for($i=0;$i<count($rows);$i++){
				$prices[] = $rows[$i]['price'];
			}
		}
		unset($inventory);
		
		return $prices;
	}
	
	// 添加自定义ID
    protected function before_insert(&$data) {
        $data['_id'] = $this->gen_product_sku();
		Doggy_Log_Helper::warn("Create new product ".$data['_id']);
		
		parent::before_insert($data);
    }
	
	/**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_values(array_unique(preg_split('/[,，;；\s]+/u',$data['tags'])));
            $data['tags'] = array_keys(array_count_values($data['tags']));
	    }

        // 分类多选
        if (isset($data['category_ids']) && !is_array($data['category_ids'])) {
            $data['category_ids'] = explode(',', $data['category_ids']);
            for($i=0;$i<count($data['category_ids']);$i++){
                $data['category_ids'][$i] = intval($data['category_ids'][$i]);
            }
        }

        // 自动生成编号
        if($data['stage']==9 && (!isset($data['number']) || empty($data['number']))){
            $data['number'] = Sher_Core_Helper_Util::getNumber();
        }

        // 库存数量不为能负数
        if(isset($data['inventory']) && (int)$data['inventory'] < 0){
            $data['inventory'] = 0;
        }

        // 是否可推广
        if(isset($data['commision_percent']) && !empty($data['commision_percent'])){
            $data['commision_percent'] = $data['commision_percent']/100;
            $data['is_commision'] = 1;
        }

        // 抢购库存数量不为能负数
        if(isset($data['snatched_count']) && (int)$data['snatched_count'] < 0){
            $data['snatched_count'] = 0;
        }

        // app抢购数量不能大于抢购总数量
        if(isset($data['app_snatched_count']) && (int)$data['app_snatched_count'] > $data['app_snatched_total_count']){
            $data['app_snatched_count'] = $data['app_snatched_total_count'];
        }

        // 积分兑换库存数量不为能负数
        if(isset($data['exchange_count']) && (int)$data['exchange_count'] < 0){
            $data['exchange_count'] = 0;
        }
		
		// 新建数据,补全默认值
		if ($this->is_saved()){
			// 添加随机数
			$data['random'] = Sher_Core_Helper_Util::gen_random();
		}
		
		// 抢购开始时间－转换为时间戳
		if(isset($data['snatched_time'])){
			$data['snatched_time'] = strtotime($data['snatched_time']);
		}
		// 抢购结束时间－转换为时间戳
		if(isset($data['snatched_end_time'])){
			$data['snatched_end_time'] = strtotime($data['snatched_end_time']);
		}
		// app抢购开始时间－转换为时间戳
		if(isset($data['app_snatched_time'])){
			$data['app_snatched_time'] = strtotime($data['app_snatched_time']);
		}
		// app抢购结束时间－转换为时间戳
		if(isset($data['app_snatched_end_time'])){
			$data['app_snatched_end_time'] = strtotime($data['app_snatched_end_time']);
		}
		// 预售开始时间，结束时间
		if(isset($data['presale_start_time'])){
			$data['presale_start_time'] = strtotime($data['presale_start_time']);
		}
		if(isset($data['presale_finish_time'])){
			$data['presale_finish_time'] = strtotime($data['presale_finish_time']) + 24*60*60 - 1;
		}
		// 投票开始时间，结束时间
		if(isset($data['voted_start_time'])){
			$data['voted_start_time'] = strtotime($data['voted_start_time']);
		}
		if(isset($data['voted_finish_time'])){
			$data['voted_finish_time'] = strtotime($data['voted_finish_time']) + 24*60*60 - 1;
		}

        // 分类标签
        if (isset($data['category_tags']) && !is_array($data['category_tags'])) {
          $data['category_tags'] = array_values(array_unique(preg_split('/[,，;；]+/u',$data['category_tags'])));
          for($i=0;$i<count($data['category_tags']);$i++){
            $data['category_tags'][$i] = trim($data['category_tags'][$i]);
          }
        }

		// 获取父级类及类组
		if (isset($data['app_category_id']) && !empty($data['app_category_id'])){
			$category = new Sher_Core_Model_Category();
			$result = $category->find_by_id((int)$data['app_category_id']);
			if (!empty($result)){
			    $data['pid'] = $result['pid'];
			}
		}
		
	    parent::before_save($data);
	}
	
    /**
	 * 保存之后事件
	 */
    protected function after_save(){
        // 如果是新的记录
        if($this->insert_mode){

            $category_model = new Sher_Core_Model_Category();
            $category_id = $this->data['category_id'];
            if(!empty($category_id)){
                $category_model->inc_counter('total_count', 1, $category_id);
                // 如果是商品，更新商品分类数量
                if($this->data['stage']==9){
                  $category_model->inc_counter('sub_count', 1, $category_id);              
                }
            }

            // 多选分类
            $category_ids = isset($this->data['category_ids']) ? $this->data['category_ids'] : array();
            if(!empty($category_ids)){
                for($i=0;$i<count($category_ids);$i++){
                    $category_model->inc_counter('total_count', 1, (int)$category_ids[$i]);
                    // 如果是商品，更新商品分类数量
                    if($this->data['stage']==9){
                      $category_model->inc_counter('sub_count', 1, (int)$category_ids[$i]);
                    }               
                }
            }

            unset($category_model);

            // 更新品牌数量
            if(!empty($this->data['brand_id'])){
                $brand_model = new Sher_Core_Model_SceneBrands();
                $brand_model->inc_counter('item_count', 1, $this->data['brand_id']);
                unset($brand_model);
            }
            
            // 更新产品总数
            Sher_Core_Util_Tracker::update_product_counter();
            
            // 仅创意投票
            if($this->data['stage'] == self::STAGE_VOTE){
                // 增加积分
                $service = Sher_Core_Service_Point::instance();
                // 提交创意
                $service->send_event('evt_new_idea', $this->data['user_id']);
            }
        }
    }
	
	/**
	 * 通过sku查找
	 */
	public function find_by_sku($sku){
		$row = $this->first(array('sku'=>(int)$sku));
        if (!empty($row)) {
            $row = $this->extended_model_row($row);
        }
		
		return $row;
	}
	
	/**
	 * 是否进入专家评估阶段
	 */
	protected function expert_assess(&$row){
		$row['expert_assess'] = false;
		// 获取最低票数
		$lowest = Doggy_Config::$vars['app.vote.lowest'];
		// 投票成功，并且投票已结束
		if (isset($row['succeed']) && !empty($row['succeed']) && $row['voted_finish_time'] < time()){
			$row['expert_assess'] = true;
			
			// 计算显示分值
			$row['score']['usability_deg'] = round(($row['score']['usability']*360)/10, 2);
			$row['score']['usability_int'] = $this->explode_point($row['score']['usability'], 0);
			$row['score']['usability_dec'] = $this->explode_point($row['score']['usability'], 1);
			
			$row['score']['design_deg'] = round(($row['score']['design']*360)/10, 2);
			$row['score']['design_int'] = $this->explode_point($row['score']['design'], 0);
			$row['score']['design_dec'] = $this->explode_point($row['score']['design'], 1);
			
			$row['score']['creativity_deg'] = round(($row['score']['creativity']*360)/10, 2);
			$row['score']['creativity_int'] = $this->explode_point($row['score']['creativity'], 0);
			$row['score']['creativity_dec'] = $this->explode_point($row['score']['creativity'], 1);
			
			$row['score']['content_deg'] = round(($row['score']['content']*360)/10, 2);
			$row['score']['content_int'] = $this->explode_point($row['score']['content'], 0);
			$row['score']['content_dec'] = $this->explode_point($row['score']['content'], 1);
		}
	}

	/**
	 * 获取封面图
	 */
	public function cover(&$row){
		// 已设置封面图
		if(isset($row['cover_id']) && !empty($row['cover_id'])){
			$asset_model = new Sher_Core_Model_Asset();
			return $asset_model->extend_load($row['cover_id']);
		}
		// 未设置Banner图，获取第一个
		$asset = new Sher_Core_Model_Asset();
		$query = array(
			'parent_id'  => (int)$row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_PRODUCT,
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
        return null;
	}

	/**
	 * 获取Banner图
	 */
	public function banner(&$row){
		// 已设置封面图
		if(isset($row['banner_id']) && !empty($row['banner_id'])){
			$asset_model = new Sher_Core_Model_Asset();
			return $asset_model->extend_load($row['banner_id']);
		}
		// 未设置Banner图，获取第一个
		$asset = new Sher_Core_Model_Asset();
		$query = array(
			'parent_id'  => (int)$row['_id'],
			'asset_type' => Sher_Core_Model_Asset::TYPE_PRODUCT_BANNER,
		);
		$data = $asset->first($query);
		if(!empty($data)){
			return $asset->extended_model_row($data);
		}
        return null;
	}
	
	/**
	 * 分割分数
	 */
	protected function explode_point($value, $index=0){
		$point = explode('.', $value);
		if ($index == 1 && count($point) == 1){
			return '00';
		}
		return $point[$index];
	}
	
	/**
	 * 获取产品不同阶段的URL
	 */
	public function gen_view_url($row){
		$stage = isset($row['stage']) ? $row['stage'] : 0;
		switch($stage) {
			case self::STAGE_VOTE:
				$view_url = Sher_Core_Helper_Url::vote_view_url($row['_id']);
				break;
			case self::STAGE_PRESALE:
				$view_url = Sher_Core_Helper_Url::sale_view_url($row['_id']);
				break;
			case self::STAGE_SHOP:
				$view_url = Sher_Core_Helper_Url::shop_view_url($row['_id']);
				break;
			case self::STAGE_EXCHANGE:
				$view_url = Sher_Core_Helper_Url::shop_view_url($row['_id']);
				break;
			case self::STAGE_IDEA:
				$view_url = Sher_Core_Helper_Url::shop_view_url($row['_id']);
				break;
			case self::STAGE_SCENE:
				$view_url = Sher_Core_Helper_Url::shop_view_url($row['_id']);
				break;
			default:
				$view_url = Doggy_Config::$vars['app.url.fever'];
				break;
		}
		
		return $view_url;
	}
	
	/**
	 * 设置封面图
	 */
	public function mark_set_cover($id, $cover_id){
		return $this->update_set($id, array('cover_id'=>$cover_id));
	}
	
    /**
     * 标记为推荐
     */
    public function mark_as_stick($id) {
        return $this->update_set((int)$id, array('stick' => 1, 'stick_on'=>time()));
    }
	
    /**
     * 取消推荐
     */
	public function mark_cancel_stick($id) {
		return $this->update_set((int)$id, array('stick' => 0));
	}

    /**
     * 标记为精选
     */
    public function mark_as_featured($id) {
        return $this->update_set((int)$id, array('featured' => 1, 'featured_on'=>time()));
    }
	
    /**
     * 取消精选
     */
	public function mark_cancel_featured($id) {
		return $this->update_set((int)$id, array('featured' => 0));
	}
	
	/**
	 * 更新产品的状态阶段
	 */
	public function mark_as_stage($id, $stage) {
		return $this->update_set($id, array('stage' => $stage));
	}
	
	/**
	 * 更新产品发布上线
	 */
    public function mark_as_published($id, $published=1) {

        $data = $this->extend_load((int)$id);

        if(empty($data)) return;
        $product = $this->load((int)$id);
        if(empty($product)){
          return;
        }

        // 不作无意义提交
        if($data['published'] == $published) return;
        //$old_stat = $data['published'];
        $ok = $this->update_set((int)$id, array('published' => $published));
        // 如果是发布状态,创建Timeline
        if($published == 1){
          // 根据类型创建timeline
          $service = Sher_Core_Service_Timeline::instance();
          $service->broad_product_published($product['user_id'], $id);
          // 更新全文索引
          Sher_Core_Helper_Search::record_update_to_dig((int)$id, 3); 
          //更新百度推送
          Sher_Core_Helper_Search::record_update_to_dig((int)$id, 12);
        }else{
          //删除索引
          Sher_Core_Util_XunSearch::del_ids('product_'.(string)$id);
        }
	}
	
	/**
	 * 通过审核后，自动设置投票起止日期
	 */
	public function mark_as_approved($id) {
		 $ok = $this->update_vote_date($id);
         if($ok['success']){
             $data = $this->load($id);
             // 增加积分
             $service = Sher_Core_Service_Point::instance();
             // 创意审核通过（进入投票环节）
             $service->send_event('evt_idea_pass', $data['user_id']);
             // 送5鸟币
            $service->make_money_in($data['user_id'], 5, "创意投票通过审核");
         }
         return $ok;
	}
	
    /**
     * 取消审核
     */
	public function mark_cancel_approved($id) {
		return $this->update_set($id, array('approved' => 0));
	}
	
	/**
	 * 重新计算产品预售数量、预售金额
	 */
	public function recount_presale_result($id, $stage=self::STAGE_PRESALE){
		// 获取所有类型
		$inventory = new Sher_Core_Model_Inventory();
		$result = $inventory->find(array(
			'product_id' => (int)$id,
			'stage' => (int)$stage,
		));
		
		$presale_count = 0;
		$presale_money = 0;
		if (!empty($result)){
			for($i=0;$i<count($result);$i++){
				$sold_count = $result[$i]['sold'] + $result[$i]['sync_count'];
				$presale_count += $sold_count;
				$presale_money += $sold_count*$result[$i]['price'];
			}
		}
		
		Doggy_Log_Helper::debug("Recount product presale:[$id],[$presale_count],[$presale_money]! ");
		
		// 开始更新
		$this->update_set((int)$id, array('presale_count' => $presale_count, 'presale_people' => $presale_count, 'presale_money' => $presale_money));
	}
	
	/**
	 * 减少产品库存，及增加已销售数量
	 */
	public function decrease_invertory($id, $quantity=1, $only=false, $add_money=0, $add_people=1, $kind=1){
		$row = $this->find_by_id((int)$id);
		
		if (empty($row)){
			throw new Sher_Core_Model_Exception('产品不存在或已被删除！');
		}
		// 仅1个sku
		if ($only){
			$add_money = $row['sale_price']*$quantity;
		}
		
		$field = ($row['stage'] == self::STAGE_PRESALE) ? 'presale_inventory' : 'inventory';
		
		// 减少库存数量
		if ($row[$field] >= $quantity){
			// 预售产品，需要累计预售金额及预售人数
			if ($row['stage'] == self::STAGE_PRESALE){
				$updated = array(
					'$inc' => array(
						'presale_count'=>$quantity, 
						'presale_inventory'=>$quantity*-1,
						'presale_people'=>$add_people,
						'presale_money'=>$add_money,
					)
				);
			} else {
				$updated = array(
					'$inc' => array('sale_count'=>$quantity, 'inventory'=>$quantity*-1),
				);
                // 如果是抢购,减少数量
                if($row['snatched']){
                    $updated = array(
                        '$inc' => array('sale_count'=>$quantity, 'inventory'=>$quantity*-1, 'snatched_count'=>-1),
                    );
                }
                // 如果是积分兑换,减少数量
                if(isset($row['exchanged']) && !empty($row['exchanged'])){
                    $updated = array(
                        '$inc' => array('sale_count'=>$quantity, 'inventory'=>$quantity*-1, 'exchange_count'=>-1),
                    );
                }
                // 如果是app闪购，增加抢购数量
                if($kind==3){
                    $updated = array(
                        '$inc' => array('sale_count'=>$quantity, 'inventory'=>$quantity*-1, 'app_snatched_count'=>$quantity),
                    );               
                }
			}
			
			return $this->update((int)$id, $updated);
		}
	}
	
	/**
	 * 恢复产品数量
	 */
	public function recover_invertory($id, $quantity=1, $only=false, $dec_money=0, $kind=1){
		$row = $this->find_by_id((int)$id);

    // 考虑到商品意外删除，订单状态无法改变，先返回true
		if (empty($row)){
      return true;
			//throw new Sher_Core_Model_Exception('产品不存在或已被删除！');
		}
		
		// 仅1个sku
		if ($only){
			$dec_money = $row['sale_price']*$quantity;
		}
		// 预售产品，需要减少累计预售金额及预售人数
		if ($row['stage'] == self::STAGE_PRESALE){
			// 恢复库存数量
			$updated = array(
				'$inc' => array(
					'presale_count'=>$quantity*-1, 
					'presale_inventory'=>$quantity,
					'presale_people'=>-1,
					'presale_money'=>$dec_money*-1,
				),
			);
		} else {
			// 恢复库存数量
			$updated = array(
				'$inc' => array('sale_count'=>$quantity*-1, 'inventory'=>$quantity),
			);
            // 恢复抢购数量
            if($row['snatched']){
                $updated = array(
				  '$inc' => array('sale_count'=>$quantity*-1, 'inventory'=>$quantity,  'snatched_count'=>1),
                );
            }
            // 恢复积分兑换数量
            if(isset($row['exchanged']) && !empty($row['exchanged'])){
                $updated = array(
				  '$inc' => array('sale_count'=>$quantity*-1, 'inventory'=>$quantity,  'exchange_count'=>1),
                );
            }
            // 恢复app闪购产品数量
            if($kind==3){
                $updated = array(
				  '$inc' => array('sale_count'=>$quantity*-1, 'inventory'=>$quantity,  'app_snatched_count'=>$quantity*-1),
                );           
            }
		}
		
		return $this->update((int)$id, $updated);
	}
	
	/**
	 * 更新专家评分
	 */	
	public function update_expert_score($id, $score, $score_count,$score_average){
		return $this->update_set($id, array('score_count'=>$score_count,
										'score_average'=>$score_average,
										'score'=>$score));
	}
	
	/**
	 * 更新最后的评价,并且comment_count+1
	 */
	public function update_last_reply($id, $user_id, $star){
		$query = array('_id'=> (int)$id);
		$new_data = array(
			'$inc' => array('comment_count'=>1, 'comment_star'=>(int)$star),
		);
		// 更新所属类别的回复数
		$this->update_category_reply_count($id);
		
		return self::$_db->update($this->collection,$query,$new_data,false,false,true);
	}
	
	/**
	 * 更新类别回复数
	 */
	public function update_category_reply_count($id){
		$row = $this->find_by_id((int)$id);
		if (!empty($row)) {
			$category = new Sher_Core_Model_Category();
			$category->inc_counter('reply_count', 1, $row['category_id']);
			unset($category);
		}
	}
	
	/**
	 * 更新投票开始、截止日期
	 * @注意：开启投票时，一定通过审核的产品
	 */
  protected function update_vote_date($id){
    $result = array('success'=>false);
    $product = $this->load((int)$id);
    if(empty($product)){
      $result['msg'] = '产品不存在!';
      return $result;
    }
    if(!empty($product['approved'])){
      $result['msg'] = '已通过审核!';
      return $result;   
    }
		// 获取时间间隔
		$interval = Doggy_Config::$vars['app.vote.interval'];
		
		// 当前时间
		$now = time();
		$date = new DateTime();
		$date->add(new DateInterval("P${interval}D"));
		$finish_time = $date->getTimestamp();
		
    $ok = $this->update_set($id, array('approved'=>1, 'voted_start_time'=>$now, 'voted_finish_time'=>$finish_time));
    if($ok){
      $result['success'] = true;
    }else{
      $result['msg'] = '操作失败!';
    }
    return $result;
	}
	
	
	/**
	 * 生成产品的SKU, SKU十位数字符
	 */
	protected function gen_product_sku($prefix='1'){
		$name = Doggy_Config::$vars['app.serialno.name'];
		
		$sku  = $prefix;
		$val = $this->next_seq_id($name);
		
		$len = strlen((string)$val);
		if ($len <= 5) {
			$sku .= date('md');
			$sku .= sprintf("%05d", $val);
		}else{
			$sku .= substr(date('md'), 0, 9 - $len);
			$sku .= $val; 
		}
		
		Doggy_Log_Helper::debug("Gen to product [$sku]");
		
		return (int)$sku;
	}
	
	/**
	 * 产生一个特定长度的数字字符串
	 */
	protected function rand_number_str($len=2, $chars='0123456789'){
        $string = '';
        for($i=0; $i<$len; $i++){
            $pos = rand(0, strlen($chars)-1);
            $string .= $chars{$pos};
        }
        return $string;
	}
	
	/**
	 * 增加计数
	 */
	public function inc_counter($field_name, $inc=1, $id=null){
		if(is_null($id)){
			$id = $this->id;
		}
		if(empty($id) || !in_array($field_name, $this->counter_fields)){
			return false;
		}
		
		return $this->inc($id, $field_name, $inc);
	}
	
	/**
	 * 减少计数
	 * 需验证，防止出现负数
	 */
	public function dec_counter($field_name,$id=null,$force=false,$count=1){
	    if(is_null($id)){
	        $id = $this->id;
	    }
	    if(empty($id)){
	        return false;
	    }
		if(!$force){
			$product = $this->find_by_id((int)$id);
			if(!isset($product[$field_name]) || $product[$field_name] <= 0){
				return true;
			}
		}
		
		return $this->dec($id, $field_name, $count);
	}
	
	/**
	 * 删除某附件
	 */
	public function delete_asset($id, $asset_id){
		// 从附件数组中删除
		$criteria = $this->_build_query($id);
		self::$_db->pull($this->collection, $criteria, 'asset', $asset_id);
		
		$this->dec_counter('asset_count', $id);
		
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->delete_file($asset_id);
		unset($asset);
	}

      /**
       * 逻辑删除
       */
      public function mark_remove($id){
        $ok = $this->update_set((int)$id, array('deleted'=>1));
        return $ok;
      }
	
	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id, $options=array()) {
		// 删除Asset
		//$asset = new Sher_Core_Model_Asset();
		//$asset->remove_and_file(array('parent_id' => $id, 'asset_type'=>array('$in'=>array(10,11,15))));
		//unset($asset);
		
		// 删除Comment
		$comment = new Sher_Core_Model_Comment();
		$comment->remove(array('target_id' => $id, 'type'=>Sher_Core_Model_Comment::TYPE_PRODUCT));
		unset($comment);

        // 删除商品情境关联表数据
        $spl_model = new Sher_Core_Model_SceneProductLink();
        $spl_list = $spl_model->find(array('product_id'=>(int)$id));
        for($i=0;$i<count($spl_list);$i++){
            $spl_model->remove((string)$spl_list[$i]['_id']);
        }
		
		// 删除TextIndex
		$textindex = new Sher_Core_Model_TextIndex();
		$textindex->remove(array('target_id' => $id));
		unset($textindex);

        //删除索引
        Sher_Core_Util_XunSearch::del_ids('product_'.(string)$id);
		
		return true;
	}

	/**
	 * 检测标题是否重复
	 */
	public function check_title($title, $type=1) {
		if(empty($title)){
			return false;
		}
		$row = $this->first(array('title' => (string)$title));
		if(!empty($row)){
			return false;
		}
		return true;
	}

	
}
