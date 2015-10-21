<?php
/**
 * 合作资源（设计品牌、院校、生成供应商）
 * @author purpen
 */
class Sher_Core_Model_Cooperation extends Sher_Core_Model_Base {
	
    protected $collection = 'cooperation';
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	# 状态标识
	const STATE_BLOCKED  = -1;
    const STATE_DISABLED = 0;
    const STATE_PENDING = 1;
    const STATE_OK = 2;
	
    protected $schema = array(
		'user_id'    => 0,
		
 		'name'       => '',
		'fullname'   => '',
		'summary'    => '',
		'keywords'   => array(),
        
        # 标识
        'marks'      => array(),
		
		# logo设置
		'logo_id'    => '',
        'logo'       => array(),
		'banner_id'  => '',
		
		'email'      => '',
		'phone'      => '',
		# 所在城市
		'city'       => '',
		'address'    => '',
        'district'   => 0,
		
		# 联系人
		'people'   	 => '',
		'mobile'     => '',
    # 职位
    'position'  => '',
		
		# 网站
		'site_url'   => '',
        # 新浪微博
        'weibo_url'  => '',
        # 微信号
        'wechat'     => '',
 		
		# 类型
		'type'         => 0,
        # 类别
        'category_ids' => array(),
        
        # 关注次数
        'follow_count' => 0,
        # 点赞数量
        'love_count'   => 0,
        # 浏览数量
        'view_count'   => 0,
        # 案例数量
        'stuff_count'  => 0,
        
		# 等级
		'rank'       => 0,
 		# 是否推荐
		'stick'      => 0,
		
		'state'      => self::STATE_DISABLED,
    );
	
    protected $required_fields = array('name');
    protected $int_fields = array('user_id','rank','type','stick','district','state');
	
    protected $counter_fields = array('follow_count', 'love_count', 'view_count', 'stuff_count');
    
	protected $joins = array(
        'banner' => array('banner_id' => 'Sher_Core_Model_Asset'),
	);
	
    //~ some event handles
    /**
	 * 保存之前,处理关键词中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['keywords']) && !is_array($data['keywords'])) {
	        $data['keywords'] = array_filter(array_values(array_unique(preg_split('/[,，\s]+/u', $data['keywords']))));
	    }
	    
        // 类别整型
        if(isset($data['category_ids'])){
            for($k=0;$k<count($data['category_ids']);$k++){
                $data['category_ids'][$k] = (int)$data['category_ids'][$k];
            }
        }
        
        // 检查是否匹配地域
        if(isset($data['city']) && !empty($data['city'])){
            $areas = new Sher_Core_Model_Areas();
            $data['district'] = $areas->match_city($data['city']);
        }
        
        $data['updated_on'] = time();
        
	    parent::before_save($data);
	}
	
	/**
	 * 保存之后事件
	 */
  	protected function after_save() {
  	  	parent::after_save();
  	}

    protected function extra_extend_model_row(&$row) {
        $id = $row['id'] = $row['_id'];
        $row['home_url'] = Sher_Core_Helper_Url::cooperate_home_url($id);
        $row['keywords_s'] = !empty($row['keywords']) ? implode(',', $row['keywords']) : '';
		if(isset($row['summary'])){
			// 转码
			$row['summary'] = htmlspecialchars_decode($row['summary']);
		
			// 去除 html/php标签
			$row['strip_summary'] = strip_tags($row['summary']);

      $row['state_label'] = '';
      switch($row['state']){
        case -1:
          $row['state_label'] = '未通过';
          break;
        case 0:
          $row['state_label'] = '待审核';
          break;
        case 1:
          $row['state_label'] = '审核中';
          break;
        case 2:
          $row['state_label'] = '已通过';
          break;
      }
		}
        
		// logo
		if(!empty($row['logo'])){
			$row['big_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($row['logo']['big'], 'avb.jpg');
			$row['medium_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($row['logo']['medium'], 'avm.jpg');
			$row['small_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($row['logo']['small'], 'avs.jpg');
			$row['mini_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($row['logo']['mini'], 'avn.jpg');
		}else{
			$row['big_avatar_url'] = Sher_Core_Helper_Url::avatar_default_url($id, 'b');
            $row['medium_avatar_url'] = Sher_Core_Helper_Url::avatar_default_url($id, 'm');
			$row['small_avatar_url'] = $row['mini_avatar_url'] = Sher_Core_Helper_Url::avatar_default_url($id, 's');
		}
        
        $this->get_ranks($row);
    }
    
	/**
	 * 更新logo
	 */
	public function update_logo($logo=array(),$id=null){
		if (is_null($id)) {
            $id = $this->id;
        }
        if (empty($id) || empty($logo)) {
            throw new Sher_Core_Model_Exception('Id or logo is NULL');
        }
		
        return $this->update_set((int)$id, array('logo'=>$logo));
	}
    
    /**
     * 获取星标数量
     */
    public function get_ranks(&$row){
        $stars = array('no','no','no','no','no');
        
        if($row['rank'] > 0){
            $stars[0] = 'active';
        }
        if($row['rank'] > 150){
            $stars[1] = 'active';
        }
        if($row['rank'] > 1500){
            $stars[3] = 'active';
        }
        if($row['rank'] > 5000){
            $stars[4] = 'active';
        }
        if($row['rank'] > 10000){
            $stars[5] = 'active';
        }
        
        $row['rank_stars'] = $stars;
    }
    
    /**
     * 星级算法,根据以下参数相关
     * view_count*2 + love_count*50 + follow_count*50
     */
    public function update_rank($type='view_count', $id=null, $coe=1){
        $offset_rank = 1;
        switch($type){
            case 'view_count':
                $offset_rank *= 2;
                break;
            case 'love_count':
                $offset_rank *= 50;
                break;
            case 'follow_count':
                $offset_rank *= 50;
                break;
        }
        return $this->inc((int)$id, 'rank', $offset_rank*$coe);
    }
    
	/**
	 * 更新用户的计数
	 */
    public function inc_counter($field_name, $id=null) {
        if (is_null($id)) {
            $id = $this->id;
        }
        if (empty($id) || !in_array($field_name, $this->counter_fields)) {
            return false;
        }
        return $this->inc((int)$id, $field_name);
    }
	
	/**
	 * 更新用户的计数
	 */
    public function dec_counter($field_name, $id=null, $force=false) {
        if (is_null($id)) {
            $id = $this->id;
        }
        if (empty($id) || !in_array($field_name, $this->counter_fields)) {
            return;
        }
		
		if(!$force){
			$cooperate = $this->find_by_id((int)$id);
			if(!isset($cooperate[$field_name]) || $cooperate[$field_name] <= 0){
				return true;
			}
		}
		
        return $this->dec((int)$id, $field_name);
    }

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}
	
}
