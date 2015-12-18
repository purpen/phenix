<?php
/**
 * 常量定义
 * @author purpen
 */
class Sher_Core_Util_Constant extends Doggy_Object {
	
	const ASSET_DOAMIN = "sher";
	
	/**
	 *  存储目录常量
	 **/
	const STROAGE_PRODUCT = 'product';
	const STROAGE_AVATAR  = 'avatar';
	const STROAGE_TOPIC   = 'topic';
	const STROAGE_TRY     = 'try';
	const STROAGE_ASSET   = 'asset';
    const STROAGE_ACTIVE  = 'active';
	const STROAGE_STUFF   = 'stuff';
	const STROAGE_COOPERATE = 'cooperate';
    const STROAGE_COMMENT = 'comment';
    const STROAGE_DEVICE = 'device';
	const STROAGE_ALBUMS = 'albums';
	const STROAGE_SPECIAL_SUBJECT = 'special_subject';
	const STROAGE_SPECIAL_COVER = 'special_cover';
	const STROAGE_STYLE_TAG = 'style_tag';
	
	/**
	 * 类型的常量
	 */
	const TYPE_DEFAULT = 0;
	const TYPE_PRODUCT = 1;
	const TYPE_TOPIC   = 2;
    const TYPE_ACTIVE  = 3;
	const TYPE_STUFF   = 4;
    const TYPE_USER    = 5;
    const TYPE_COOPERATE = 6; // 资源
    const TYPE_CASE = 7;  // 案例
    const TYPE_ALBUM = 8; // 专辑
    const TYPE_SPECIAL_SUBJECT = 9; // 专题
	
	// 来源站点
	const FROM_LOCAL = 1;
	const FROM_WEIBO = 2;
	const FROM_QQ = 3;
	const FROM_ALIPAY = 4;
	const FROM_WEIXIN = 5;
	const FROM_WAP = 6;
	const FROM_IAPP = 7;  // iphone,ipad
	const FROM_APP_ANDROID = 8; // android
    
    /**
     * 事件类型
     */
	# 发表/提交
	const EVT_POST = 1;
    
	# 发布
	const EVT_PUBLISH = 2;
	
	# 回应
	const EVT_REPLY = 3;
	
	# 评价
	const EVT_COMMENT = 4;
	
	# 收藏
	const EVT_FAVORITE = 5;
	
    # 喜欢
    const EVT_LOVE = 6;

    # 关注者
    const EVT_FOLLOW = 7;
    
    # 关注
    const EVT_FOLLOWING = 8;

    # 分享
    const EVT_SHARE = 9;
    
    # 投票
    const EVT_VOTE = 10;
	
	
	# 第三方支付
	const TRADE_ALIPAY = 1;
	const TRADE_QUICKPAY = 2;
	const TRADE_WEIXIN = 3;
	const TRADE_TENPAY = 4;
	
	
	# 临时订单过期时间， 10小时
	const EXPIRE_TIME = 36000;
	
	# 预售订单过期时间，15分钟
	const PRESALE_EXPIRE_TIME = 900;
	
	# 普通订单过期时间，48小时
	const COMMON_EXPIRE_TIME = 172800;

  # 实验室会员支付订单过期时间，15分钟
  const D3IN_EXPIRE_TIME = 900;
	
	/**
	 * DigList推荐Id
	 */
	const DIG_TOPIC_TOP = 'dig_topic_top';  // 全部置顶主题列表
    # 版块置顶
    const DIG_TOPIC_CATEGROY = 'dig_topic_category';
    
    # 大赛抽奖统计
    const DIG_MATCH_PRAISE_STAT = 'dig_match_praise_stat';

    #CES线下抽奖
    const DIG_CES_PRAISE_STAT = 'ces_praise_stat';
	
	const FEATURED_STUFF = 'featured_stuff_list';

    # 大赛参加省份统计
    const DIG_MATCH2_PROVINCE = 'match2_province_top';
    # 大赛参加大学统计
    const DIG_MATCH2_COLLEGE = 'match2_college_top';

    # 搜索任务记录最后创建日期
    const DIG_XUN_SEARCH_LAST_TIME = 'xun_search_last_time';

    # 搜索任务记录更新的对象ID,以便定时更新到索引-----话题
    const DIG_XUN_SEARCH_RECORD_TOPIC_UPDATE_IDS = 'xun_search_record_topic_update_ids';
    const DIG_XUN_SEARCH_RECORD_TOPIC_FAIL_IDS = 'xun_search_record_topic_fail_ids';
    # 搜索任务记录更新的对象ID,以便定时更新到索引-----灵感
    const DIG_XUN_SEARCH_RECORD_STUFF_UPDATE_IDS = 'xun_search_record_stuff_update_ids';
    const DIG_XUN_SEARCH_RECORD_STUFF_FAIL_IDS = 'xun_search_record_stuff_fail_ids';
    # 搜索任务记录更新的对象ID,以便定时更新到索引-----商品
    const DIG_XUN_SEARCH_RECORD_PRODUCT_UPDATE_IDS = 'xun_search_record_product_update_ids';
    const DIG_XUN_SEARCH_RECORD_PRODUCT_FAIL_IDS = 'xun_search_record_product_fail_ids';

    # 优质内容ID记录,用于主动推送至百度-----话题,灵感,商品
    const DIG_PUSH_BAIDU_TOPIC_IDS = 'push_baidu_topic_ids';
    const DIG_PUSH_BAIDU_STUFF_IDS = 'push_baidu_stuff_ids';
    const DIG_PUSH_BAIDU_PRODUCT_IDS = 'push_baidu_product_ids';

    # 签到每日统计数目
    const DIG_SIGN_EVERY_DAY_STAT = 'sign_every_day_stat';

    # 云马Ｃ1专题活动 名嘴争霸
    const DIG_SUBJECT_YMC1_01 = 'subject_ymc1_01';
    const DIG_SUBJECT_02 = '2';
    # 奶妈奶爸PK
    const DIG_SUBJECT_03 = 'subject_03';

    # 第三方站点访问量统计
    const DIG_THIRD_SITE_STAT = 'third_site_stat';
    # 签到抽奖统计
    const DIG_SIGN_DRAW_RECORD = 'sign_draw_record';

    /**
     * DigList stuff计数器
     */
    const STUFF_COUNTER  = 'stuff_counter';
    
    const FEVER_COUNTER  = 'fever_counter';
	
	
	/**
	 * 微博账户自动创建时默认设置密码
	 */
	const WEIBO_AUTO_PASSWORD = 'weibo#2014';
	
	/**
	 * QQ账户自动创建时默认设置密码
	 */
	const QQ_AUTO_PASSWORD = 'qq#2014';
	
	/**
	 * 微信账户自动创建时默认设置密码
	 */
	const WX_AUTO_PASSWORD = 'wx#2014';
	
	
    /**
     * 已过期订单
     * 
     * @var int
     */
    const ORDER_EXPIRED = -1;
    
    /**
     * 已取消的订单
     * 
     * @var int
     */
    const ORDER_CANCELED = 0;
    
    /**
     * 等待付款状态
     * 
     * @var int
     */
    const ORDER_WAIT_PAYMENT = 1;
	
    /**
     * 等待审核状态
     * 
     * @var int
     */
    const ORDER_WAIT_CHECK = 5;
	
	/**
	 * 订单支付失败
	 * @var int
	 */
	const ORDER_PAY_FAIL = 6;
	
    /**
     * 正在配货状态
     * 
     * @var int
     */
    const ORDER_READY_GOODS = 10;

    /**
     * 申请退款
     * 
     * @var int
     */
    const ORDER_READY_REFUND = 12;

    /**
     * 已退款成功
     * 
     * @var int
     */
    const ORDER_REFUND_DONE = 13;
	
    /**
     * 订单已发货状态
     * 
     * @var int
     */
    const ORDER_SENDED_GOODS = 15;
	
    /**
     * 订单已完成状态
     * 
     * @var int
     */
    const ORDER_PUBLISHED = 20;

    /**
     * 用户积分事件状态 - 新事件
     *
     * @var int
     */
    const EVENT_STATE_NEW = 1;

    /**
     * 用户积分事件状态 - 已锁定处理中
     *
     * @var int
     */
    const EVENT_STATE_LOCK = 10;

    /**
     * 用户积分事件状态 - 已处理
     *
     * @var int
     */
    const EVENT_STATE_DONE = 100;
    

    const TRANS_TYPE_IN = 1;

    const TRANS_TYPE_OUT = -1;

    /**
     * 初始交易
     */
    const TRANS_STATE_INIT = 0;
    /**
     * 事务进行中
     */
    const TRANS_STATE_PENDING = 10;
    /**
     * 事务完成
     */
    const TRANS_STATE_OK = 100;
    /**
     * 事务已经取消
     */
    const TRANS_STATE_CANCELED = -1;

    /*
     * 未记账
     */
    const POINT_ACCOUNT_STATE_NEW = 0;
    /**
     * 已记账
     */
    const POINT_ACCOUNT_STATE_DOING = 1;
    /**
     * 已结帐
     */
    const POINT_ACCOUNT_STATE_DONE = 0;
    
    
    ## 记数器
    const USER_AUTO_GEN_COUNT = 'user_auto_gen';
    
    const PHONE_AUTO_GET_COUNT = 'phone_auto_gen';
    
    
    /**
     * 版块置顶key
     */
    public static function top_topic_category_key($category_id){
        return self::DIG_TOPIC_CATEGROY.'_'.$category_id;
    }

  /**
   * 蛋年报名选项解析--所属领域
   */
  public static function birdegg_area_options($id=0){
    $array = array(1=>'智能家居', 2=>'智能可穿戴', 3=>'无人机', 4=>'机器人', 5=>'3D打印', 6=>'媒体', 10=>'其它');
    if(empty($id)){
      return $array;
    }else{
      return $array[(int)$id];
    }
  }

  /**
   * 蛋年报名选项解析--感兴趣的
   */
  public static function birdegg_interest_options($id=0){
    $array = array(1=>'我需要媒体', 2=>'我需要融资', 3=>'我需要工业设计', 4=>'我需要元器件', 5=>'我需要销售渠道', 10=>'其它');
    if(empty($id)){
      return $array;
    }else{
      return $array[(int)$id];
    }
  }

  /**
   * 专题分享统计-ID转名称
   */
  public static function subject_share_name($id=0){
    $array = array(1=>'支持原创设计', 2=>'2015京东众筹', 3=>'蛋年(深圳)', 4=>'太火鸟招聘', 5=>'实验室活动', 6=>'分享送红包', 7=>'火眼计划', 8=>'(云马C1)神嘴争霸赛', 9=>'金投赏', 10=>'试用抽奖－云马c1', 11=>'京东造逆', 12=>'京东bigger2', 13=>'(奶爸奶妈)神嘴争霸赛', 14=>'亿航活动(评论分享点赞)',15=>'签到抽奖',
      16=>'小蚁行车记录仪',
      17=>'奶爸配奶机',
      18=>'--',
      19=>'--',
    );
    if(empty($id)){
      return $array;
    }else{
      return $array[(int)$id];
    }
  }


}
