<?php
/**
 * 常量定义
 */
class Sher_Core_Util_Constant extends Doggy_Object {
	
	
	const ASSET_DOAMIN = "sher";
	
	/**
	 *  存储目录常量
	 **/
	const STROAGE_PRODUCT = 'product';
	const STROAGE_AVATAR  = 'avatar';
	const STROAGE_TOPIC   = 'topic';
	const STROAGE_ASSET   = 'asset';
	
	
	/**
	 * 类型的常量
	 */
	const TYPE_DEFAULT = 0;
	const TYPE_PRODUCT = 1;
	const TYPE_TOPIC   = 2;
	
	
	# 临时订单过期时间， 10小时
	const EXPIRE_TIME = 36000;
	
	/**
	 * DigList推荐Id
	 */
	const DIG_TOPIC_TOP = 'dig_topic_top';  // 全部置顶主题列表
	
	
	
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
     * 正在配货状态
     * 
     * @var int
     */
    const ORDER_READY_GOODS = 10;
	
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
	
}
?>