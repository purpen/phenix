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
	
    protected $schema = array(
		'_id'     => null,
		# taobao sku
		'taobao_iid' => null,
		# 产品名称
	    'title'   => '',
		# 优势/亮点
		'advantage' => '',
		# 简述
        'summary' => '',
		# 详情内容
		'content' => '',
		# 产品标签
    	'tags'    => array(),
		
		# 产品视频链接
		'video' => array(),
		
		# 访问地址
		'view_url' => '',
		
		# 封面图
 		'cover_id' => '',
		'asset' => array(),
		# 附件图片数
		'asset_count' => 0,
		
		# 类别支持多选
		'category_id' => 0,
		
		# 上传者
	    'user_id' => null,
		# 设计者/团队
		'designer_id' => 0,
		
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
		'snatched_time' => 0,
		# 预约人数
		'appoint_count' => 0,
    # 抢购价
    'snatched_price' => 0,
    # 抢购数量
    'snatched_count' => 0,

	    ## 试用
	    'trial' =>  0,
		
		## 计数器
		
		# 浏览数
    	'view_count'=>0,
		# 收藏数
        'favorite_count' => 0, 
		# 喜欢数
        'love_count' => 0,
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
		
		# 产品周期 (投票、预售、销售)
		'stage' => self::STAGE_VOTE,
		
		# 是否在投票列表显示
		'process_voted' => 0,
		# 是否在预售列表显示
		'process_presaled' => 0,
		# 是否在商店列表显示
		'process_saled' => 0,
		
		# 投票申请是否审核
		'approved' => 0,
		# 投票是否成功
		'succeed' => 0,
		# 预售，销售产品是否发布
    	'published' => 0,
		
		# 推荐（编辑推荐、推荐至首页）
		'stick' => 0,
		
		# 是否成功案例产品
		'okcase' => 0,
		
		# 状态
		'state' => 0,
    	# 删除标识
    	'deleted' => 0,
		# 随机数
		'random' => 0,
    # 最近一次编辑人ID
    'last_editor_id' => 0,
    );
	
	protected $required_fields = array('user_id','title');
	
	protected $int_fields = array('user_id','designer_id','category_id','inventory','sale_count','presale_count','presale_people', 'mode_count','appoint_count','state','published','deleted','process_voted','process_presaled','process_saled','presale_inventory','snatched_count','stuff_count','last_editor_id');
	
	protected $float_fields = array('cost_price', 'market_price', 'sale_price', 'hot_price', 'presale_money', 'presale_goals', 'snatched_price');
	
	protected $counter_fields = array('inventory','sale_count','presale_count', 'mode_count','asset_count', 'view_count', 'favorite_count', 'love_count', 'comment_count','topic_count','vote_favor_count','vote_oppose_count','appoint_count','stuff_count');
	protected $retrieve_fields = array('content'=>0);
	
	protected $joins = array(
	    'user'  => array('user_id'  => 'Sher_Core_Model_User'),
		'designer' => array('designer_id'  => 'Sher_Core_Model_User'),
	    'cover' => array('cover_id' => 'Sher_Core_Model_Asset'),
		'category' => array('category_id' => 'Sher_Core_Model_Category'),
	);
	
	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		if(!isset($row['view_url']) || empty($row['view_url'])){
			$row['view_url'] = $this->gen_view_url($row);
		}
		$row['mm_view_url'] = sprintf(Doggy_Config::$vars['app.url.mm_shop.view'], $row['_id']);
		$row['wap_view_url'] = sprintf(Doggy_Config::$vars['app.url.wap.shop.view'], $row['_id']);
		$row['subject_view_url'] = Sher_Core_Helper_Url::product_subject_url($row['_id']);
		$row['vote_view_url'] = Sher_Core_Helper_Url::vote_view_url($row['_id']);
		$row['presale_view_url'] = Sher_Core_Helper_Url::sale_view_url($row['_id']);
		
		$row['tags_s'] = !empty($row['tags']) ? implode(',',$row['tags']) : '';
		$row['vote_count'] = $row['vote_favor_count'] + $row['vote_oppose_count'];
		
		if ($row['stage'] == self::STAGE_VOTE){
			$row['stage_label'] = '投票中';
			// 计算投票完成比
			$lowest = Doggy_Config::$vars['app.vote.lowest'];
			$row['vote_percent'] = sprintf("%.1f", $row['vote_favor_count']/$lowest);
		}else if ($row['stage'] == self::STAGE_PRESALE){
			$row['stage_label'] = '预售中';
		}else if ($row['stage'] == self::STAGE_SHOP){
			$row['stage_label'] = '热售中';
		}else{
			$row['stage_label'] = '未设置'; // 未知
		}
		
		// HTML 实体转换为字符
		if (isset($row['content'])){
			$row['content'] = htmlspecialchars_decode($row['content']);
		}
		
		// 去除 html/php标签
		$row['strip_summary'] = strip_tags(htmlspecialchars_decode($row['summary']));
		
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
		$row['voted_finished'] = ($row['voted_finish_time'] < time()) ? true : false;
		
		// 预售是否结束
		$row['presale_finished'] = ($row['presale_finish_time'] < time()) ? true : false;
		
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
			return $data['inventory'] > 0;
		}
		return false;
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
	        $data['tags'] = array_values(array_unique(preg_split('/[,，\s]+/u',$data['tags'])));
	    }

    //库存数量不为能负数
    if(isset($data['inventory']) && (int)$data['inventory']<0){
      $data['inventory'] = 0;
    }

    //抢购库存数量不为能负数
    if(isset($data['snatched_count']) && (int)$data['snatched_count']<0){
      $data['snatched_count'] = 0;
    }
		
		// 新建数据,补全默认值
		if ($this->is_saved()){
			// 添加随机数
			$data['random'] = Sher_Core_Helper_Util::gen_random();
		}
		
		// 转换为时间戳
		if(isset($data['snatched_time'])){
			$data['snatched_time'] = strtotime($data['snatched_time']);
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
		
	    parent::before_save($data);
	}
	
	/**
	 * 保存之后事件
	 */
  protected function after_save() {
    //如果是新的记录
    if($this->insert_mode) {
      $category_id = $this->data['category_id'];
      if (!empty($category_id)) {
        $category = new Sher_Core_Model_Category();
        $category->inc_counter('total_count', 1, $category_id);
        unset($category);
      }
      
      // 更新产品总数
      Sher_Core_Util_Tracker::update_product_counter();
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
		if ($row['succeed'] && $row['voted_finish_time'] < time()){
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
	protected function gen_view_url($row){
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
        return $this->update_set($id, array('stick' => 1));
    }
	
    /**
     * 取消推荐
     */
	public function mark_cancel_stick($id) {
		return $this->update_set($id, array('stick' => 0));
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

    //不作无意义提交
    if($data['published']==$published) return;
    //$old_stat = $data['published'];
    $ok = $this->update_set((int)$id, array('published' => $published));
    //如果是发布状态,创建Timeline
    if($published==1){
      //根据类型创建timeline 
      if($ok){
        switch ($data['stage']){
          case self::STAGE_VOTE:
            $evt = Sher_Core_Model_Timeline::EVT_VOTE;
            break;
          case self::STAGE_PRESALE:
            $evt = Sher_Core_Model_Timeline::EVT_PRESELL;
            break;
          case self::STAGE_SHOP:
            $evt = Sher_Core_Model_Timeline::EVT_SHOP;
            break;
          default:
            $evt = 0;
        }
        $timeline = new Sher_Core_Model_Timeline();
        $arr = array(
          'user_id' => $data['user_id'],
          'target_id' => $data['_id'],
          'type' => Sher_Core_Model_Timeline::TYPE_PRODUCT,
          'evt' => $evt,
        );
        $timeline->broad_events($arr['evt'], $arr['user_id'], $arr['target_id'], $arr['type']);
      }
    }
	}
	
	/**
	 * 通过审核后，自动设置投票起止日期
	 */
	public function mark_as_approved($id) {
		return $this->update_vote_date($id);
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
	public function decrease_invertory($id, $quantity=1, $only=false, $add_money=0, $add_people=1){
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
        //如果是抢购,减少数量
        if($row['snatched']){
          $updated = array(
            '$inc' => array('sale_count'=>$quantity, 'inventory'=>$quantity*-1, 'snatched_count'=>-1),
          );
        }
			}
			
			return $this->update((int)$id, $updated);
		}
	}
	
	/**
	 * 恢复产品数量
	 */
	public function recover_invertory($id, $quantity=1, $only=false, $dec_money=0){
		$row = $this->find_by_id((int)$id);
		
		if (empty($row)){
			throw new Sher_Core_Model_Exception('产品不存在或已被删除！');
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
      //恢复抢购数量
      if($row['snatched']){
        $updated = array(
				  '$inc' => array('sale_count'=>$quantity*-1, 'inventory'=>$quantity,  'snatched_count'=>1),
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
		// 获取时间间隔
		$interval = Doggy_Config::$vars['app.vote.interval'];
		
		// 当前时间
		$now = time();
		$date = new DateTime();
		$date->add(new DateInterval("P${interval}D"));
		$finish_time = $date->getTimestamp();
		
		return $this->update_set($id, array('approved'=>1, 'voted_start_time'=>$now, 'voted_finish_time'=>$finish_time));
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
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		// 删除Asset
		$asset = new Sher_Core_Model_Asset();
		$asset->remove_and_file(array('parent_id' => $id));
		unset($asset);
		
		// 删除Comment
		$comment = new Sher_Core_Model_Comment();
		$comment->remove(array('target_id' => $id));
		unset($asset);
		
		// 删除TextIndex
		$textindex = new Sher_Core_Model_TextIndex();
		$textindex->remove(array('target_id' => $id));
		unset($textindex);
		
		return true;
	}
	
}
?>
