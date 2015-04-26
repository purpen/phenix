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
	
	/**
	 * 类型的常量
	 */
	const TYPE_DEFAULT = 0;
	const TYPE_PRODUCT = 1;
	const TYPE_TOPIC   = 2;
    const TYPE_ACTIVE  = 3;
	const TYPE_STUFF   = 4;
    const TYPE_USER    = 5;
    const TYPE_COOPERATE = 6;
    const TYPE_CASE = 7;
	
	// 来源站点
	const FROM_LOCAL = 1;
	const FROM_WEIBO = 2;
	const FROM_QQ = 3;
	const FROM_ALIPAY = 4;
	const FROM_WEIXIN = 5;
	const FROM_WAP = 6;
	const FROM_IAPP = 7;
    
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
	
	/**
	 * DigList推荐Id
	 */
	const DIG_TOPIC_TOP = 'dig_topic_top';  // 全部置顶主题列表
    # 版块置顶
    const DIG_TOPIC_CATEGROY = 'dig_topic_category';
    
    # 大赛抽奖统计
    const DIG_MATCH_PRAISE_STAT = 'dig_match_praise_stat';
	
	const FEATURED_STUFF = 'featured_stuff_list';

    # 大赛参加省份统计
    const DIG_MATCH2_PROVINCE = 'match2_province_top';
    # 大赛参加大学统计
    const DIG_MATCH2_COLLEGE = 'match2_college_top';

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
    
    
    /**
     * 版块置顶key
     */
    public static function top_topic_category_key($category_id){
        return self::DIG_TOPIC_CATEGROY.'_'.$category_id;
    }
}
