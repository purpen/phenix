<?php
/**
 * Model user
 * @author purpen
 */
class Sher_Core_Model_User extends Sher_Core_Model_Base {
    protected $collection = 'user';
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
	
	const STATE_BLOCKED  = -1;
    const STATE_DISABLED = 0;
    const STATE_PENDING = 1;
    const STATE_OK = 2;
	
	// 用户角色
    const ROLE_USER   = 1;
    
	const ROLE_EDITOR = 5;
    // 主编、运营主管
    const ROLE_CHIEF  = 6;
    // 客服人员
    const ROLE_CUSTOMER = 7;
    const ROLE_ADMIN  = 8;
    const ROLE_SYSTEM = 9;
    
    const PERMIT_POST = 'p';
    
    // 保密性别
    const SEX_HIDE = 0;
	const SEX_MALE = 1;
	const SEX_FEMALE = 2;

    // 婚姻状况
	const MARR_SINGLE = 11;
	const MARR_LOVE = 12;
	const MARR_TWO = 22;
	
	// 来源站点
	const FROM_LOCAL = 1;
	const FROM_WEIBO = 2;
	const FROM_QQ = 3;
	const FROM_ALIPAY = 4;
	const FROM_WEIXIN = 5;
	
	// 专家身份
	protected $mentors = array(
		array(
			'id' => 0,
			'name' => '无'
		),
		array(
			'id' => 1,
			'name' => '工业设计'
		),
		array(
			'id' => 2,
			'name' => '视觉设计'
		),
		array(
			'id' => 3,
			'name' => '交互设计'
		),
		array(
			'id' => 4,
			'name' => '用户研究'
		),
		array(
			'id' => 5,
			'name' => '软件工程师'
		),
		array(
			'id' => 9,
			'name' => '硬件工程师'
		),
		array(
			'id' => 11,
			'name' => '市场营销'
		),
		array(
			'id' => 15,
			'name' => 'VC投资'
		),
		array(
			'id' => 20,
			'name' => '渠道销售'
		),
		array(
			'id' => 25,
			'name' => '生产供应商'
		),
		array(
			'id' => 30,
			'name' => '零组件供应商'
		),
		array(
			'id' => 35,
			'name' => '方案解决商'
		),
		array(
			'id' => 50,
			'name' => '官方认证'
    ),
		array(
			'id' => 51,
			'name' => '品牌认证'
		),
		array(
			'id' => 52,
			'name' => '设计师认证'
		),
		array(
			'id' => 40,
			'name' => '其他'
		),
	);

	// 用户身份
	protected $kinds = array(
		array(
			'id' => 0,
			'name' => '无'
		),
		array(
			'id' => 1,
			'name' => '员工'
		),
		array(
			'id' => 6,
			'name' => '短信营销'
		),
		array(
			'id' => 7,
			'name' => 'ajax快捷注册'
		),
		array(
			'id' => 8,
			'name' => '快捷注册'
		),
		array(
			'id' => 9,
			'name' => '小号'
		),
		array(
			'id' => 10,
			'name' => '硬件工程师'
		),
	);

	// 用户认证
	protected $symbols = array(
		array(
			'id' => 0,
			'name' => '无'
		),
		array(
			'id' => 1,
			'name' => '官方认证'
		),
		array(
			'id' => 2,
			'name' => '企业认证'
		),
		array(
			'id' => 3,
			'name' => '--'
		),

	);
	
    protected $roles = array(
        'user' => self::ROLE_USER,
		'editor' => self::ROLE_EDITOR,
        'chief' => self::ROLE_CHIEF,
        'customer' => self::ROLE_CUSTOMER,
        'admin' => self::ROLE_ADMIN,
        'system' => self::ROLE_SYSTEM,
    );
	
    public static $TWEET_EVENTS = array('post','comment','like','love');
	
    protected $schema = array(
		# 用户名
		'account'  => null,
		'password' => null,
		'nickname' => null,

		# 唯一登录标识
		'phone_account' => null,
		'email_account' => null,
		
		'email'    => null,
		
		'invitation' => null,
        'state'      => self::STATE_PENDING,
		
        'role_id'    => self::ROLE_USER,
		'permission' => array(),
		
		# 专家身份，默认为0
		'mentor' => 0,
		
        # sina weibo
        'sina_uid' => null,
        'sina_access_token' => null,
		
		# qq open
		'qq_uid' => null,
		'qq_access_token' => null,
		
		# weixin open
		'wx_open_id' => null,
		'wx_access_token' => null,
		# weixin unionid 
		'wx_union_id' => null,
		
        'last_login'    => 0,
		'current_login' => 0,
	    'online_alive'  => 0,
		
        ## counter
		# 关注数
        'follow_count'  => 0,
		# 粉丝数
		'fans_count'    => 0,
		# 图片数量
        'photo_count'   => 0,
		# 喜欢数量
		'love_count'    => 0,
		# 主题数量
		'topic_count'   => 0,
		# 产品数量
		'product_count' => 0,
		# 灵感数量
		'stuff_count'   => 0,
        # 收藏数量
        'favorite_count' => 0,
        
        # 情景数量
        'scene_count' => 0,
        # 场景数量
        'sight_count' => 0,
        
        # 订阅情景数量
        'subscription_count' => 0,
        # 场景点赞数量
        'sight_love_count' => 0,
		
		## 初次登录导向
		'first_login'   => 1,
		
		## 头像设置
		'avatar' => array(),
        #　用户头图
        'head_pic' => null,
		
		'digged' => 0,
		
		## 个人偏好设置
		'setting' => array(
			'_' => null,
			'layout' => null,
		),
		
        'profile' => array(
            'realname' => null,
			'phone'    => null,
			// 民族
			'nation' => null,
			// 籍贯
			'born_place' => null,
            'card' => null,
            // 用户工作类型
            'job'  => null,
            'school' => null,
            'address' => null,
            'zip' => null,
            'im_qq' => null,
            'weixin' => null,
			// 身高
			'height' => null,
			// 体重
			'weight' => null,
			// 婚姻状况
			'marital' => self::MARR_SINGLE,
			// 出生年月日
            'age'  => array(),
            // 所在公司
            'company' => null,
            // 所属行业
            'industry' => null,
            // 省份
            'province_id' => 0,
            // 城市
            'district_id' => 0,
            // 用户标签
            'label' => null,
            // 达人标签
            'expert_label' => null,
            // 达人身份
            'expert_info' => null,
              // 年龄段: A.60后；B.70后；C.80后；D.90后；E.00后；F.--；
              'age_group' => null,
              // 阶层: A.月光族；B.小资；C.新中产；D.土豪；E.大亨；F.--；
              'assets' => null,
              // 感兴趣的情景主题(分类id数组)
              'interest_scene_cate' => array(),
        ),

		// 所在城市
		'city' => null,
        // 所在区域ID(暂时不用,在profile里)
        'district' => 0,
		// 性别
		'sex' => self::SEX_HIDE,
		// 个人关键词
		'tags' => array(),
		// 个人介绍
        'summary' => null,
		
		// 计数器
		'counter' => array(
			'message_count' => 0, // 私信
			'notice_count' => 0,  // 通知 #
			'alert_count' => 0, // 提醒 #
			'fans_count' => 0,  // 粉丝 #
			'comment_count' => 0, // 评论 #
			'people_count' => 0,  // 用户 #
      'fiu_alert_count' => 0, // Fiu提醒
            'fiu_comment_count' => 0, // Fiu 评论
            'fiu_notice_count' => 0, // Fiu 通知
            'sight_love_count' => 0, // Fiu 别人给他点的赞(场景)
            'order_wait_payment' => 0, // 待付款
            'order_ready_goods' => 0, // 待发货
            'order_sended_goods' => 0, // 待收货
            'order_evaluate' => 0, // 待评价
            'fiu_bonus_count' => 0, // 红包提醒数
		),
		// 用户行为记录
		'visit' => array(
			'new_user_viewed' => 0,
		),

    #用户其它认证标记
    'identify' => array(
      // 实验室 志愿者
      'd3in_volunteer' => 0,
      // 实验室 会员
      'd3in_vip' => 0,
      // 实验室 标记 --证明参与过实验室预约
      'd3in_tag' => 0,
      // 是否订阅过情景
      'is_scene_subscribe' => 0,
      // 是否app首次下单
      'is_app_first_shop' => 0,
      // 是否达人标识0.未申请；-1.审核中；-2.拒绝；1.通过；
      'is_expert' => 0,
      // 所属联盟ID
      'alliance_id' => '',
      // 所属联盟code
      'referral_code' => '',
      // 地盘 ID
      'storage_id' => '',
      // 是否是地盘管理员
      'is_storage_manage' => 0,
    ),

    # 用户其它标识说明
    'identify_info' => array(
      'position' => 0,
      'user_name' => null,
    ),
		# 来源站点(注册方式)
		'from_site' => Sher_Core_Util_Constant::FROM_LOCAL,
        # 来源: 1.默认；8.小程序；
        'from_to' => 1,

        # 用户唯一邀请码
        'invite_code' => null,
        
        # 是否为优质用户(可跳过作品审核)
        'quality' => 0,

        # 标记: 1.内部员工 V 6.短信营销 7.ajax快捷注册 8.快捷注册; 9.为小号, 10.地盘管理员创建子账户, 20.第三方直接登录用户,没有绑定手机号, 21.短信注册(随机密码),22.iPad店铺下单创建用户;
        'kind' => 0,
        # symbol认证
        'symbol' => 0,

        # 是否绑定手机(未兼容老数据)
        'is_bind' => 0,
        # 达人认证
        'verified' => 0,
        # 最后一次登录IP统计
        'last_ip' => null,
    );
	
	protected $retrieve_fields = array('account'=>1,'nickname'=>1,'email'=>1,'avatar'=>1,'state'=>1,'role_id'=>1,'permission'=>1,'first_login'=>1,'profile'=>1,'city'=>1,'sex'=>1,'tags'=>1,'summary'=>1,'created_on'=>1,'from_site'=>1,'fans_count'=>1,'mentor'=>1,'topic_count'=>1,'product_count'=>1,'counter'=>1,'quality'=>1,'follow_count'=>1,'love_count'=>1,'favorite_count'=>1,'kind'=>1,'identify'=>1,'identify_info'=>1,'sina_uid'=>1,'qq_uid'=>1,'wx_open_id'=>1,'wx_union_id'=>1,'symbol'=>1,'last_ip'=>1,'age'=>1,'head_pic'=>1, 'scene_count'=>1, 'sight_count'=>1, 'subscription_count' => 1, 'sight_love_count' => 1, 'from_to'=>1);
	
    protected $required_fields = array('account', 'password');

    protected $int_fields = array('role_id','state','role_id','marital','sex','height','weight','mentor','district','quality','kind','symbol','from_to');
    
	protected $counter_fields = array('follow_count', 'fans_count', 'photo_count', 'love_count', 'favorite_count', 'topic_count', 'product_count', 'stuff_count', 'subscription_count', 'sight_love_count', 'scene_count', 'sight_count');
	
	protected $joins = array(
        //'cover' =>  array('head_pic' => 'Sher_Core_Model_Asset'),
    );
	
    //~ some event handles
    /**
	 * 保存之前,处理标签中的逗号,空格等
	 */
	protected function before_save(&$data) {
	    if (isset($data['tags']) && !is_array($data['tags'])) {
	        $data['tags'] = array_filter(array_values(array_unique(preg_split('/[,，\s]+/u',$data['tags']))));
	    }
	    $data['updated_on'] = time();
        
        // 检查是否匹配地域
        if(isset($data['city']) && !empty($data['city'])){
            $areas = new Sher_Core_Model_Areas();
            $data['district'] = $areas->match_city($data['city']);
        }
        
	    parent::before_save($data);
	}
	
	/**
	 * 保存之后事件
	 */
    protected function after_save() {
        // 更新用户总数
        if($this->insert_mode){
            Sher_Core_Util_Tracker::update_user_counter();
            parent::after_save();
            $user_id = $this->data['_id'];
            // print 'init user-id:'.$user_id;
            // 初始化会员扩展状态表记录
            $model = new Sher_Core_Model_UserExtState();
            $model->init_record($user_id);
            $model = new Sher_Core_Model_UserPointQuota();
            $model->init_record($user_id);
            $model = new Sher_Core_Model_UserPointBalance();
            $model->touch_init_record($user_id);
        }
    }
	
	/**
	 * 验证用户信息
	 */
    protected function validate() {
		// 新建记录
		if($this->insert_mode){
			if (!$this->_check_account()){
				throw new Sher_Core_Model_Exception('账户已被占用，请更换！');
			}
		}
		
        return true;
    }

	/**
	 * 检测账户是否唯一
	 */
	public function check_account($account, $type=1) {
		if(empty($account)){
			return false;
		}
		$row = $this->first(array('account' => (string)$account));
		if(!empty($row)){
			return false;
		}
		return true;
	}
	
	/**
	 * 检测账户是否唯一
	 */
	protected function _check_account() {
		$account = $this->data['account'];
		if(empty($account)){
			return false;
		}
		Doggy_Log_Helper::debug("Validate user account[$account]!");
		$row = $this->first(array('account' => $account));
		if(!empty($row)){
			return false;
		}
		return true;
	}
	
	/**
	 * 检查昵称是否唯一
	 */
	public function _check_name($nickname=null, $user_id=0) {
		if (is_null($nickname)){
      	    $nickname = $this->data['nickname'];
		}
		if(empty($nickname)){
			return false;
		}

		Doggy_Log_Helper::debug("Validate user name[$nickname]!");
		$rows = $this->find(array('nickname' => $nickname));
		if(empty($rows)){
			return true;
    	}else{
      	  	//判断是否更新状态
      	   if($user_id != 0){
        	   if(count($rows) == 1){
				   if($rows[0]['_id'] == $user_id) {
					   return true;
				   }
          	   	   return false;
        	   }else{
          		   return false;
        	   }
      	 	}else{
        		return false;
      	 	}
    	}
	}
	
	/**
	 * 获取默认profile
	 */
	public function get_profile(){
		$default_profile = array(
            'realname' => null,
			'phone'    => null,
			// 民族
			'nation' => null,
			// 籍贯
			'born_place' => null,
            'card' => null,
            // 用户工作类型
            'job'  => null,
            'school' => null,
            'address' => null,
            'zip' => null,
            'im_qq' => null,
            'weixin' => null,
			// 身高
			'height' => null,
			// 体重
			'weight' => null,
			// 婚姻状况
			'marital' => self::MARR_SINGLE,
			// 出生年月日
			'age'  => array(),
      // 所在公司
      'company' => null,
      // 所属行业
      'industry' => null,
      // 省份
      'province_id' => 0,
      // 城市
      'district_id' => 0,
      // 用户标签
      'label' => null,
      // 达人标签
      'expert_label' => null,
      // 达人身份
      'expert_info' => null,
      // 年龄段: A.60后；B.70后；C.80后；D.90后；E.00后；F.--；
      'age_group' => null,
      // 阶层: A.月光族；B.小资；C.新中产；D.土豪；E.大亨；F.--；
      'assets' => null,
      // 感兴趣的情景主题(分类id数组)
      'interest_scene_cate' => array(),
        );
		return $default_profile;
	}
    
    protected function extra_extend_model_row(&$row) {
        $id = $row['id'] = $row['_id'];
        // 如果是手机号,中间段以*显示
        $row['true_nickname'] = $row['nickname'];
        if(!empty($row['nickname']) && strlen((int)$row['nickname'])==11){
            $row['nickname'] = substr((int)$row['nickname'],0,3)."****".substr((int)$row['nickname'],7,4);
        }else{
          // 如果是第三方注册，昵称过长，自动截取前10
          $n_count = Sher_Core_Helper_Util::strlen_mb($row['nickname']);
          if($n_count>=15){
              $row['nickname'] = Sher_Core_Helper_Util::substr_mb($row['nickname'], 0, 15);
          }       
        }


		// 显示名称
		$row['screen_name'] = !empty($row['nickname']) ? $row['nickname'] : '火鸟人';
		
		// 用户头像
		if(!empty($row['avatar'])){
			$row['big_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($row['avatar']['big'], 'avb.jpg');
			$row['medium_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($row['avatar']['medium'], 'avm.jpg');
			$row['small_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($row['avatar']['small'], 'avs.jpg');
			$row['mini_avatar_url'] = Sher_Core_Helper_Url::avatar_cloud_view_url($row['avatar']['mini'], 'avn.jpg');		
		}else{
			$row['big_avatar_url'] = Sher_Core_Helper_Url::avatar_default_url($id, 'b');
            $row['medium_avatar_url'] = Sher_Core_Helper_Url::avatar_default_url($id, 'm');
			$row['small_avatar_url'] = $row['mini_avatar_url'] = Sher_Core_Helper_Url::avatar_default_url($id, 's');
		}
		
        $row['home_url'] = Sher_Core_Helper_Url::user_home_url($id);
        $row['view_follow_url'] = Sher_Core_Helper_Url::user_follow_list_url($id);
        $row['view_fans_url'] = Sher_Core_Helper_Url::user_fans_list_url($id);
        $row['is_ok'] = $row['state'] == self::STATE_OK;
        if ($row['role_id'] == self::ROLE_SYSTEM){
        	$row['is_system'] = $row['is_admin'] = $row['is_editor'] = $row['is_customer'] = $row['is_chief'] = true;
        	$row['can_system'] = $row['can_admin'] = $row['can_edit'] = true;
        }
        if ($row['role_id'] == self::ROLE_ADMIN){
            $row['is_admin'] = $row['is_editor'] = $row['is_customer'] = $row['is_chief'] = true;
            $row['can_admin'] = $row['can_edit'] = true;
        }
        // 客服人员
        if ($row['role_id'] == self::ROLE_CUSTOMER){
            $row['is_customer'] = true;
            $row['can_admin'] = $row['can_service'] = true;
        }
        // 主编、运营人员
        if ($row['role_id'] == self::ROLE_CHIEF){
            $row['is_chief']  = $row['is_editor'] = true;
            $row['can_admin'] = $row['can_edit'] = true;
        }
        if ($row['role_id'] == self::ROLE_EDITOR){
            $row['is_editor'] = true;
            $row['can_edit']  = true;
        }
		
        if (!empty($row['permission']) && in_array(self::PERMIT_POST,$row['permission'])) {
            $row['can_post'] = true;
        }
		
		if(empty($row['mentor'])){
			$row['mentor_info'] = array('name' => isset($row['profile']['job'])?$row['profile']['job']:'');
		}else{
			$row['mentor_info'] = $this->find_mentors($row['mentor']);
		}
        
        $row['last_char'] = substr((string)$id, -1);
        $row['ext_state'] = DoggyX_Model_Mapper::load_model($row['_id'], 'Sher_Core_Model_UserExtState');

        if(isset($row['profile']['age']) && !empty($row['profile']['age'])){
            $row['birthday'] = implode('-', $row['profile']['age']);
        }else{
            $row['birthday'] = '';       
        }
    }
	
	/**
	 * 获取全部组或某个
	 */
	public function find_mentors($id=0){
		if($id){
			for($i=0;$i<count($this->mentors);$i++){
				if ($this->mentors[$i]['id'] == $id){
					return $this->mentors[$i];
				}
			}
			return array();
		}
		return $this->mentors;
	}

	/**
	 * 获取用户身份或某个
	 */
	public function find_kinds($id=0){
		if($id){
			for($i=0;$i<count($this->kinds);$i++){
				if ($this->kinds[$i]['id'] == $id){
					return $this->kinds[$i];
				}
			}
			return array();
		}
		return $this->kinds;
	}

	/**
	 * 获取用户认证
	 */
	public function find_symbols($id=0){
		if($id){
			for($i=0;$i<count($this->symbols);$i++){
				if ($this->symbols[$i]['id'] == $id){
					return $this->symbols[$i];
				}
			}
			return array();
		}
		return $this->symbols;
	}
	
	/**
	 * 设置用户身份
	 */
	public function update_mentor($id, $mentor){
		// 验证是否设定身份
		$result = $this->find_mentors((int)$mentor);
		if(empty($result)){
			throw new Sher_Core_Model_Exception('Update user mentor ['.$mentor.'] not defined!');
		}
		return $this->update_set((int)$id, array('mentor' => (int)$mentor));
	}

	/**
	 * 设置类型kind 
	 */
	public function update_kind($id, $kind){
		// 验证是否设定身份
		$result = $this->find_kinds((int)$kind);
		if(empty($result)){
			throw new Sher_Core_Model_Exception('Update user kind ['.$kind.'] not defined!');
		}
		return $this->update_set((int)$id, array('kind' => (int)$kind));
	}

	/**
	 * 设置认证symbol
	 */
	public function update_symbol($id, $symbol){
		// 验证是否设定身份
		$result = $this->find_symbols((int)$symbol);
		if(empty($result)){
			throw new Sher_Core_Model_Exception('Update user symbol ['.$symbol.'] not defined!');
		}
		return $this->update_set((int)$id, array('symbol' => (int)$symbol));
	}
	
    /**
     * 判断是否是编辑
     */
    public function can_edit() {
        return empty($this->data)?false:($this->data['role_id'] == self::ROLE_EDITOR || $this->data['role_id'] == self::ROLE_CHIEF || $this->data['role_id'] == self::ROLE_SYSTEM || $this->data['role_id'] == self::ROLE_ADMIN);
    }
	
    /**
     * 判断是否是管理员
     */
    public function is_admin() {
        return empty($this->data)?false:($this->data['role_id'] == self::ROLE_SYSTEM || $this->data['role_id'] == self::ROLE_ADMIN);
    }
    /**
     * 判断是否是客服人员
     */
    public function is_customer() {
        return empty($this->data)?false:($this->data['role_id'] == self::ROLE_CUSTOMER);
    }
    /**
     * 判断是否是主编或运营人员
     */
    public function is_chief() {
        return empty($this->data)?false:($this->data['role_id'] == self::ROLE_CHIEF);
    }
    /**
     * 判断是否有管理权限
     */
    public function can_admin() {
        return empty($this->data)?false:($this->data['role_id'] == self::ROLE_SYSTEM || $this->data['role_id'] == self::ROLE_ADMIN || $this->data['role_id'] == self::ROLE_CHIEF || $this->data['role_id'] == self::ROLE_CUSTOMER);
    }
    /**
     * 判断是否是系统管理员
     */
    public function can_system() {
        return empty($this->data)?false:($this->data['role_id'] == self::ROLE_SYSTEM);
    }
    
    public function can_post() {
        return empty($this->data)?false:($this->data['role_id'] == self::ROLE_SYSTEM
            || $this->data['role_id'] == self::ROLE_ADMIN
            || (!empty($this->data['permission']) && in_array(self::PERMIT_POST,$this->data['permission'])));
    }
	
	/**
	 * 是否是第一次登录
	 */
	public function first_login() {
		if(empty($this->data)){
			return false;
		}
		if(isset($this->data['first_login']) && $this->data['first_login'] == 1){
			return true;
		}
		return false;
	}
	
	/**
	 * 设置完成向导
	 */
	public function completed_guide($id) {
		return $this->update_set((int)$id, array('first_login' => 0));
	}
	

    public function approve_post($user_id) {
        return $this->update($user_id,array(
            '$addToSet' => array(
                'permission' => self::PERMIT_POST,
            )));
    }
	
    public function revoke_post($user_id) {
        return self::$_db->pull($this->collection,array('_id' => (int)$user_id),'permission',self::PERMIT_POST);
    }
	
    /**
     * 记录用户最后一次动作的心跳时间,用于判断用户是否属于在线状态
     *
     * @param int $user_id
     * @param int $time
     * @return void
     */
    public function heartbeat($user_id=null,$time=null) {
        if (is_null($user_id)) {
            $user_id = $this->id;
        }
        $user_id = (int) $user_id;
        $update['online_alive'] = is_null($time)?time():(int)$time;
        return $this->update_set($user_id,$update);
    }
	
    /**
     * 修改用户的角色
     *
     * @param string $id
     * @param string $new_role user|system|admin
     * @return bool
     */
    public function change_user_role($id,$new_role) {
        if (empty($new_role) || !isset($this->roles[$new_role])) {
            throw new Doggy_Model_ValidateException("未知的role:$new_role");
        }
        $role_id = $this->roles[$new_role];
        return $this->update_set((int)$id, array('role_id' => $role_id));
    }
	
	/**
	 * 推荐用户,orderby越大，排序越靠前
	 */
	public function digged_user($id){
		return $this->inc(array('_id' => (int)$id),'digged');
	}
	
    public function touch_last_login($user_id=null,$time=null) {
        if (is_null($user_id)) {
            $user_id = $this->id;
        }
        if (is_null($time)) {
            $time = time();
        }
		// 同时更新上次登录时间及IP
		$row = $this->find_by_id($user_id);
		if(!empty($row)){
			$last_login = $row['current_login'];
			if(!isset($row['visit'])){
				$row['visit'] = array();
			}
			$visit = $row['visit'];
			// 设置距离上次用户登录新增用户的标识
			$visit['new_user_viewed'] = 1;
      $ip = Sher_Core_Helper_Auth::get_ip();
			$this->update_set((int)$user_id, array('current_login'=>(int)$time, 'last_login'=> $last_login, 'visit' => $visit, 'last_ip'=>$ip));
		}
    }
	
	/**
	 * 更新用户行为记录标识
	 */
	public function update_visit_field($user_id, $field, $value=0) {
		if(!in_array($field,array('new_user_viewed'))){
			return;
		}
		$this->update_set((int)$user_id, array('visit.'.$field => $value));
	}

	/**
    * 更新用户其它身份标识
    * 实验室
	 */
	public function update_user_identify($user_id, $field, $value=0) {
		if(!in_array($field,array('d3in_volunteer', 'd3in_vip', 'd3in_tag', 'is_scene_subscribe', 'is_app_first_shop', 'is_expert', 'is_storage_manage'))){
			return;
		}
		return $this->update_set((int)$user_id, array('identify.'.$field => $value));
	}

    /**
     * 禁用账户
     */
    public function block_account($id) {
        // 请求sso系统
        $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
        // 是否请求sso验证
        if ($sso_validated) {
            $user = $this->load((int)$id);
            if (!$user) {
                return false;
            }
            $sso_params = array(
                'name' => $user['account'],
                'evt' => 1,
                'status' => 0,
            );
            $sso_result = Sher_Core_Util_Sso::common(4, $sso_params);
            if (!$sso_result['success']) {
                return false; 
            }
        }
        return $this->update_set((int)$id, array('state' => self::STATE_DISABLED));
    }

    /**
     * 解封/激活帐号
     */
    public function active_account($id){
        // 请求sso系统
        $sso_validated = Doggy_Config::$vars['app.sso']['validated'];
        // 是否请求sso验证
        if ($sso_validated) {
            $user = $this->load((int)$id);
            if (!$user) {
                return false;
            }
            $sso_params = array(
                'name' => $user['account'],
                'evt' => 1,
                'status' => 1,
            );
            $sso_result = Sher_Core_Util_Sso::common(4, $sso_params);
            if (!$sso_result['success']) {
                return false; 
            }
        }
    	  return $this->update_set((int)$id, array('state' => self::STATE_OK));
    }

    /**
     * 设置优质
     */
    public function set_quality($id, $evt=0){
        return $this->update_set((int)$id, array('quality'=> (int)$evt));  
    }
	
	/**
	 * 更新微博用户授权access token
	 */
	public function update_weibo_accesstoken($id, $accesstoken){
		return $this->update_set((int)$id, array('sina_access_token' => $accesstoken));
	}
	
	/**
	 * 更新QQ用户授权access token
	 */
	public function update_qq_accesstoken($id, $accesstoken){
		return $this->update_set((int)$id, array('qq_access_token' => $accesstoken));
	}

	/**
	 * 更新WeiXin用户授权access token
	 */
	public function update_wx_accesstoken($id, $accesstoken){
		return $this->update_set((int)$id, array('wx_access_token' => $accesstoken));
	}
	
	/**
	 * 更新密码
	 */
	public function update_password($id, $newpassword){
		return $this->update_set((int)$id, array('password' => sha1($newpassword)));
	}
	
	/**
	 * 更新用户的计数
	 */
    public function inc_counter($field_name, $user_id=null) {
        if (is_null($user_id)) {
            $user_id = $this->id;
        }
        
        if (empty($user_id) || !in_array($field_name, $this->counter_fields)) {
            return false;
        }
        
        return $this->inc(array('_id' => (int)$user_id), $field_name);
    }
	
	/**
	 * 更新用户的计数
	 */
    public function dec_counter($field_name, $user_id=null, $force=false) {
        if (is_null($user_id)) {
            $user_id = $this->id;
        }
        if (empty($user_id) || !in_array($field_name, $this->counter_fields)) {
            return;
        }
		
		if(!$force){
			$user = $this->find_by_id((int)$user_id);
			if(!isset($user[$field_name]) || $user[$field_name] <= 0){
				return true;
			}
		}
		
        return $this->dec(array('_id' => (int)$user_id), $field_name);
    }
	
	/**
	 * 更新用户头像
	 */
	public function update_avatar($avatar=array(),$user_id=null){
		if (is_null($user_id)) {
            $user_id = $this->id;
        }
        if (empty($user_id) || empty($avatar)) {
            throw new Sher_Core_Model_Exception('user_id or avatar is NULL');
        }
		
        return $this->update_set((int) $user_id,array('avatar'=>$avatar));
	}
	
    /**
     * 更新基本资料
     */
    public function update_profile($profile,$user_id=null) {
        if ($user_id==null) {
            $user_id = $this->id;
        }
        if (empty($user_id)) {
            throw new Sher_Core_Model_Exception('user_id is NULL');
        }
        $user_id = (int) $user_id;
        
        return $this->update_set($user_id,array('profile' => $profile));
    }
    
    public function update_contact($contact,$user_id=null) {
        if ($user_id==null) {
            $user_id = $this->id;
        }
        if (empty($user_id)) {
            throw new Sher_Core_Model_Exception('user_id is NULL');
        }
        $user_id = (int) $user_id;
        
        return $this->update_set($user_id,array('contact' => $contact));
    }
	
	/**
	 * 更新计数器
     *
	 */
	public function update_counter($user_id,$field,$value=0){
		if(!in_array($field,array('message_count','notice_count','alert_count','fans_count','comment_count','people_count','fiu_alert_count','fiu_comment_count','fiu_notice_count','order_wait_payment','order_ready_goods','order_sended_goods','order_evaluate','fiu_bonus_count'))){
			return;
		}
		$this->update_set((int)$user_id, array('counter.'.$field => $value));
	}
	
	/**
	 * 更新计数器，累加,减少
	 */
	public function update_counter_byinc($user_id, $field, $value=1){
		if(!in_array($field,array('message_count','notice_count','alert_count','fans_count','comment_count','people_count','fiu_alert_count','fiu_comment_count','fiu_notice_count','order_wait_payment','order_ready_goods','order_sended_goods','order_evaluate','fiu_bonus_count'))){
			return;
    }
    // 不能为负
    if($value<0){
      $user = $this->load($user_id);
      $count = isset($user['counter'][$field]) ? $user['counter'][$field] : 0;
      if($count+$value<0){
        $value = $count*-1;
      }
    }
		$counter_name = 'counter.'.$field;
		return $this->inc(array('_id'=>(int)$user_id), $counter_name, $value, true);
	}
    
  /**
	 * 批量更新附件所属
	 */
	public function update_batch_assets($ids=array(), $parent_id){
		if (!empty($ids)){
			$model = new Sher_Core_Model_Asset();
			foreach($ids as $id){
				$model->update_set($id, array('parent_id' => (int)$parent_id));
			}
		}
	}

}
