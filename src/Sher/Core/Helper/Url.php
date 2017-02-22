<?php
/**
 * 构造访问地址
 */
class Sher_Core_Helper_Url {
	
	/**
	 * 云存储 附件URL
	 */
	public static function asset_qiniu_view_url($key,$style=null){
		$asset_url = Doggy_Config::$vars['app.url.qiniu.frbird'].'/'.$key;
		if (!is_null($style)){
			$asset_url .= '-'.$style;
		}
		return $asset_url;
	}
	
	/**
	 * 附件的URL
	 */
	public static function asset_view_url($path,$domain='sher'){
		$asset_url = Sher_Core_Util_Asset::getAssetUrl($domain,$path);
		return $asset_url;
	}
	
	/**
	 * 用户头像
	 */
	public static function avatar_cloud_view_url($key, $style=null){
		$avatar_url = Doggy_Config::$vars['app.url.qiniu.frbird'].'/'.$key;
		if (!is_null($style)){
			$avatar_url .= '-'.$style;
		}
		return $avatar_url;
	}
	
	/**
	 * 用户默认头像
	 */
	public static function avatar_default_url($user_id, $type='m'){
		$avatar_default = Doggy_Config::$vars['app.url.packaged'].'/images/deavatar/';
        $avatar_file = '00'.substr((string)$user_id, -1);
		switch ($type) {
		    case 'b':
		        $avatar_default .= $avatar_file.'.jpg';
		        break;
		    case 'm':
		        $avatar_default .= $avatar_file.'-m.jpg';
		        break;
		    case 's':
		        $avatar_default .= $avatar_file.'-s.jpg';
		        break;
		}
        
		return $avatar_default;
	}
	
	/**
	 * 帖子列表访问地址
	 */
    public static function topic_list_url($category_id=null, $type=null, $time=null, $sort=null, $page=null) {
        if (!is_null($category_id)) {
            $category_id = 'c'.$category_id;
        }
		
        if (!empty($page)) {
            $page = "p${page}.html";
        }
		
        return self::build_url_path('app.url.topic', $category_id, $type, $time, $sort).$page;
    }

	/**
	 * 活动列表访问地址
	 */
    public static function active_list_url($category_id=null, $page=null) {
        if (!is_null($category_id)) {
            $category_id = 'c'.$category_id;
        }
		
        if (!empty($page)) {
            $page = "p${page}.html";
        }
		
        return self::build_url_path('app.url.active', $category_id).$page;
    }
	
	/**
	 * 帖子列表访问地址,优化URL格式
	 */
	public static function topic_advance_list_url($category_id, $type, $time, $sort,$page=1) {
		return  sprintf(Doggy_Config::$vars['app.url.topic.list'], $category_id, $type, $time, $sort, $page);
	}
	
	/**
	 * 帖子查看地址
	 */
    public static function topic_view_url($topic_id,$page=1){
    	return sprintf(Doggy_Config::$vars['app.url.topic.view'], $topic_id, $page);
    }

	/**
	 * 活动查看地址
	 */
    public static function active_view_url($active_id,$page=1){
    	return sprintf(Doggy_Config::$vars['app.url.active.view'], $active_id, $page);
    }

	/**
	 * wap活动查看地址
	 */
    public static function wap_active_view_url($active_id,$page=1){
    	return sprintf(Doggy_Config::$vars['app.url.wap.active.view'], $active_id, $page);
    }
	
	/**
	 * Wap帖子查看地址
	 */
    public static function wap_topic_view_url($topic_id,$page=1){
    	return sprintf(Doggy_Config::$vars['app.url.wap.social.show'], $topic_id, $page);
    }
	
	/**
	 * 产品话题查看地址
	 */
	public static function product_subject_url($product_id, $page=1){
		return sprintf(Doggy_Config::$vars['app.url.product.subject'], $product_id, $page);
	}
	
	/**
	 * 投票列表访问地址
	 */
    public static function vote_list_url($category_id=null, $type=null, $sort=null, $page=null) {
        if (!is_null($category_id)) {
            $category_id = 'c'.$category_id;
        }
		
        if (!empty($page)) {
            $page = "p${page}.html";
        }
		
        return self::build_url_path('app.url.fever', $category_id, $type, $sort).$page;
    }
	
	/**
	 * 投票列表访问地址,优化URL格式
	 */
	public static function vote_advance_list_url($category_id, $type, $sort, $page=1) {
		return  sprintf(Doggy_Config::$vars['app.url.fever.list'], $category_id, $type, $sort, $page);
	}
	
	/**
	 * 产品投票查看地址
	 */
    public static function vote_view_url($id){
    	return  sprintf(Doggy_Config::$vars['app.url.fever.view'], $id);
    }
	
	/**
	 * 产品预售查看地址
	 */
    public static function sale_list_url($category_id=null, $type=null, $sort=null, $page=null) {
        if (!is_null($category_id)) {
            $category_id = 'c'.$category_id;
        }
		
        if (!empty($page)) {
            $page = "p${page}.html";
        }
		
        return self::build_url_path('app.url.sale', $category_id, $type, $sort).$page;
    }
	
	/**
	 * 产品预售查看地址
	 */
    public static function sale_view_url($id){
    	return  sprintf(Doggy_Config::$vars['app.url.sale.view'], $id);
    }
	
	/**
	 * 产品销售查看地址
	 */
    public static function shop_list_url($category_id=null, $type=null, $sort=null, $page=null) {
        if (!is_null($category_id)) {
            $category_id = 'c'.$category_id;
        }
		
        if (!empty($page)) {
            $page = "p${page}.html";
        }
		
        return self::build_url_path('app.url.shop', $category_id, $type, $sort).$page;
    }
	
	/**
	 * 产品销售查看地址
	 */
    public static function shop_view_url($id, $page=1){
    	return sprintf(Doggy_Config::$vars['app.url.shop.view'], $id, $page);
    }
	
    /**
     * 大赛地址
     */
    public static function contest_view_url($id){
        return  sprintf(Doggy_Config::$vars['app.url.contest.show'], $id);
    }

    /**
     * 大赛地址--wap
     */
    public static function wap_contest_view_url($id){
        return  sprintf("%s/contest/show?id=%d", Doggy_Config::$vars['app.url.wap'], $id);
    }
    
	/**
	 * 订单详情查看地址
	 */
    public static function order_view_url($rid){
    	return  sprintf(Doggy_Config::$vars['app.url.my.order_view'], $rid);
    }
	
	/**
	 * wap订单详情查看地址
	 */
    public static function order_mm_view_url($rid){
    	return  sprintf(Doggy_Config::$vars['app.url.my.order_mm_view'], $rid);
    }

	/**
	 * 实验室-订单详情查看地址
	 */
    public static function d_order_view_url($rid){
    	return  sprintf(Doggy_Config::$vars['app.url.my.d_order_view'], $rid);
    }
	
	/**
	 * 实验室-wap订单详情查看地址
	 */
    public static function d_order_wap_view_url($rid){
    	return  sprintf(Doggy_Config::$vars['app.url.my.d_order_wap_view'], $rid);
    }
	
	/**
	 * 跟踪推荐位查看地址
	 */
    public static function ad_view_url($id){
    	return  sprintf(Doggy_Config::$vars['app.url.advertise.view'], $id);
    }
	
    /**
     * 相关的话题列表地址
     */
    public static function user_topic_list_url($user_id, $page=null, $t=null) {
        if(!empty($page)){
            $page = "p${page}.html";
        }
        if(!empty($t)){
            $t = "t${t}";
        }
        
		return self::build_url_path('app.url.user', $user_id, 'topics', $t).$page;
    }
    
    /**
     * 他人喜欢的列表地址
     */
    public static function user_like_list_url($user_id, $page=null) {
        if (!empty($page)) {
            $page = "p${page}.html";
        }
		return self::build_url_path('app.url.user',$user_id,'like').$page;
    }
	
    /**
     * 他人发起的列表地址
     */
    public static function user_submitted_list_url($user_id, $page=null) {
        if (!empty($page)) {
            $page = "p${page}.html";
        }
		return self::build_url_path('app.url.user',$user_id,'submitted').$page;
    }
	
    /**
     * 他人支持的列表地址
     */
    public static function user_support_list_url($user_id, $page=null) {
        if (!empty($page)) {
            $page = "p${page}.html";
        }
		return self::build_url_path('app.url.user',$user_id,'support').$page;
    }
	
	
    /**
     * 他人关注的列表地址
     */
    public static function user_follow_list_url($user_id, $page=null) {
        if (!empty($page)) {
            $page = "p${page}.html";
        }
		return self::build_url_path('app.url.user', $user_id, 'follow').$page;
    }
	
    
    
    /**
     * 他人粉丝的列表地址
     */
    public static function user_fans_list_url($user_id, $page=null) {
        if (!empty($page)) {
            $page = "p${page}.html";
        }
        return self::build_url_path('app.url.user', $user_id, 'fans').$page;
    }
	
	/**
	 * 管理申请列表
	 */
    public static function admin_reply_list_url($state,$page=null) {
        if (!empty($page)) {
            $page = "?page=${page}";
        }
        return self::build_url_path('app.url.admin.reply','state',$state).$page;
    }

	/**
	 * 管理举报列表
	 */
    public static function admin_report_list_url($state,$page=null) {
        if (!empty($page)) {
            $page = "?page=${page}";
        }
        return self::build_url_path('app.url.admin.report','state',$state).$page;
    }
    
    

	/**
	 * 我分享的图片地址
	 */
    public static function my_share_list_url($page=null) {
        if (!empty($page)) {
            $page = "p${page}.html";
        }
        return self::build_url_path('app.url.my','share').$page;
    }

	/**
	 * 我收藏的图片地址
	 */
    public static function my_like_list_url($page=null) {
        if (!empty($page)) {
            $page = "p${page}.html";
        }
        return self::build_url_path('app.url.my','like').$page;
    }
    
    /**
     * 我专辑的图片地址
     */
    public static function my_album_list_url($page=null) {
        if (!empty($page)) {
            $page = "p${page}.html";
        }
        return self::build_url_path('app.url.my','album').$page;
    }
    
    /**
     * 我喜欢的图片地址
     */
    public static function my_love_list_url($page=null) {
        if (!empty($page)) {
            $page = "p${page}.html";
        }
        return self::build_url_path('app.url.my','love').$page;
    }
    
    /**
     * 我关注的用户列表地址
     */
    public static function my_follow_list_url($page=null){
        if (!empty($page)) {
            $page = "p${page}.html";
        }
        return self::build_url_path('app.url.my','follow').$page;
    }
    
    /**
     * 我粉丝的用户列表地址
     */
    public static function my_fans_list_url($page=null){
    	if (!empty($page)) {
            $page = "p${page}.html";
        }
        return self::build_url_path('app.url.my','fans').$page;
    }

    /**
     * 关注用户分享图片列表
     */
    public static function follow_stuff_list_url($page=null){
        if (!empty($page)) {
            $page = "p${page}.html";
        }
		return self::build_url_path('app.url.stuff','follow').$page;
    }
    
	/**
	 * 产品分类访问地址
	 */
    public static function stuff_list_url($category_id=null, $page=null) {
        if (!is_null($category_id)) {
            $category_id = 'c'.$category_id;
        }
        if (!empty($page)) {
            $page = "p${page}.html";
        }
        return self::build_url_path('app.url.stuff', $category_id).$page;
    }
	
    /**
     * 产品分享浏览地址
     */
    public static function stuff_view_url($stuff_id, $page=1){
    	return  sprintf(Doggy_Config::$vars['app.url.stuff.view'], $stuff_id, $page);
    }

    /**
     * 蛋年分享浏览地址
     */
    public static function birdegg_view_url($stuff_id){
    	return  sprintf(Doggy_Config::$vars['app.url.birdegg']."/%s.html", $stuff_id);
    }

    /**
     * 十万火计2浏览地址
     */
    public static function match2_view_url($stuff_id){
    	return  sprintf(Doggy_Config::$vars['app.url.contest']."/view2/%s.html", $stuff_id);
    }

    /**
     * 产品合作浏览地址
     */
    public static function incubator_view_url($id){
    	return  sprintf(Doggy_Config::$vars['app.url.incubator']."/view?id=%s", $id);
    }

    /**
     * 产品分享浏览地址
     */
    public static function wap_stuff_view_url($stuff_id){
    	return  sprintf(Doggy_Config::$vars['app.url.wap.stuff.view'], $stuff_id);
    }
	
	/**
	 * 产品灵感评论链接
	 */
	public static function stuff_comment_url($stuff_id, $page='#p#'){
		return  sprintf(Doggy_Config::$vars['app.url.stuff.comment'], $stuff_id, $page);
	}

    /**
     * 举报分享地址
     */
    public  static function stuff_report_url($stuff_id){
    	return  sprintf(Doggy_Config::$vars['app.url.stuff.report'],$stuff_id);
    }

    /**
     * 根据 $id 显示缩略图
     */
    public static function thumb_view_url($id){
        return sprintf(Doggy_Config::$vars['app.url.thumb'], $id);
    }

  	/**
     * 将参数作为url的path添加到指定的config key定义的url,生成一个友好的伪静态地址.
     *
     * @param string $url_config_key 指定的config key用于url前缀
     * @param string $force_trailing_slash
     * @return void
     */
    public static function build_url_path() {
        $args = func_get_args();
        if (empty($args)) {
            return '';
        }
        $key = array_shift($args);
        $url = Doggy_Config::$vars[$key];
        $url = rtrim($url,'/');
        while ($path = array_shift($args)) {
            $url .= '/'.$path;
        }
		
        return substr($url,-1,1) != '/' ? $url.'/':$url;
    }
	
    public static function user_home_url($id){
       return self::build_url_path('app.url.user',$id);
    }
	
    public static function asset_url($id) {
        return sprintf(Doggy_Config::$vars['app.url.asset_view'],$id);
    }
	
	public static function asset_ori_url($file_id) {
        return sprintf(Doggy_Config::$vars['app.url.asset_ori'],$file_id);
    }
    
    /**
     * 孵化资源
     */
    public static function cooperate_home_url($id){
        return sprintf(Doggy_Config::$vars['app.url.cooperate.view'], $id);
    }

    /**
     * 孵化资源-wao
     */
    public static function wap_cooperate_home_url($id){
        return sprintf(Doggy_Config::$vars['app.url.wap']."/incubator/view?id=%d", $id);
    }
    
	/**
	 * 设置向导
	 */
	public static function get_guide_url(){
		return self::build_url_path('app.url.guide');
	}

}

