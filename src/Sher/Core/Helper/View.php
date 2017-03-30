<?php
/**
 * 设置view的辅助工具
 *
 * @package default
 */
class Sher_Core_Helper_View {

    public static function setup_deploy_information(&$stash) {
        // inject version information to view layout
        $stash['sher_version'] = Doggy_Config::$vars['app.version.sher'];
        // css/js bundle/version
        $stash['js_use_bundle'] = Doggy_Config::$vars['app.js.use_bundle'];
        $stash['js_jquery_bundle_version'] = Doggy_Config::$vars['app.version.jquery_bundle_version'];
        $stash['css_use_bundle'] = Doggy_Config::$vars['app.css.use_bundle'];
        $stash['css_bundle_version'] = Doggy_Config::$vars['app.version.css_bundle_version'];
		$stash['app_mode'] = Doggy_Config::$vars['app.mode'];
        $stash['app_disable_cached'] = Doggy_Config::$vars['app.disable_cached'];
	}
    /**
     * 设置主菜单url
     *
     * @param string $stash
     * @return void
     */
    public static function setup_site_menu(&$stash) {
        // top site menu
        $stash['site_menu_index'] = Sher_Core_Helper_Url::build_url_path('app.url.index');
        $stash['site_menu_hot'] = Sher_Core_Helper_Url::build_url_path('app.url.stuff','hot');
        $stash['site_menu_latest'] = Sher_Core_Helper_Url::build_url_path('app.url.stuff','latest');
        $stash['site_menu_rank'] = Sher_Core_Helper_Url::build_url_path('app.url.index','sort','rank');
        
        $stash['site_menu_follow'] = Sher_Core_Helper_Url::build_url_path('app.url.stuff','follow');

        $stash['site_menu_my'] = Sher_Core_Helper_Url::build_url_path('app.url.my');
        $stash['site_menu_search'] = Sher_Core_Helper_Url::build_url_path('app.url.search');
        $stash['site_menu_tag'] = Sher_Core_Helper_Url::build_url_path('app.url.tag');

        $stash['site_menu_login'] = Sher_Core_Helper_Url::build_url_path('app.url.auth','login');
        $stash['site_menu_register'] = Sher_Core_Helper_Url::build_url_path('app.url.auth','register');
        $stash['site_menu_logout'] = Sher_Core_Helper_Url::build_url_path('app.url.auth','logout');
        $stash['site_menu_forget'] = Sher_Core_Helper_Url::build_url_path('app.url.auth','forget');
        $stash['site_menu_invite'] = Sher_Core_Helper_Url::build_url_path('app.url.my','invite');

		$stash['site_menu_admin'] = Doggy_Config::$vars['app.url.admin'];
        $stash['site_menu_admin_report'] = Sher_Core_Helper_Url::build_url_path('app.url.admin','report');
        $stash['site_menu_admin_reply'] = Sher_Core_Helper_Url::build_url_path('app.url.admin','reply');
        $stash['site_menu_admin_trash'] = Sher_Core_Helper_Url::build_url_path('app.url.admin','trash');
		$stash['site_menu_admin_category'] = Sher_Core_Helper_Url::build_url_path('app.url.admin','category');

    }
    
    // 通用城市列表
    public static $city = array(
        array(
            'id' => '0001',
            'name' => '北京', // 39.8707070000,116.3666050000
            'image_url' => 'http://frbird.qiniudn.com/asset/160525/57456cfbfc8b12af2f8b468c-1-p750x422.jpg',
            'lng' => 116.3666050000, // 经度
            'lat' => 39.8707070000, // 纬度
            'is_estore' => true,
            'is_scene' => true,
        ),
        array(
            'id' => '0002',
            'name' => '上海', // 31.2363050000,121.4802370000
            'image_url' => 'http://frbird.qiniudn.com/asset/160525/57456cfbfc8b12af2f8b468c-2-p750x422.jpg',
            'lng' => 121.4802370000, // 经度
            'lat' => 31.2363050000, // 纬度
            'is_estore' => true,
            'is_scene' => true,
        ),
        array(
            'id' => '0003',
            'name' => '广州', // 23.1353080000,113.2707930000
            'image_url' => 'http://frbird.qiniudn.com/asset/160525/57456cfbfc8b12af2f8b468c-3-p750x422.jpg',
            'lng' => 113.2707930000, // 经度
            'lat' => 23.1353080000, // 纬度
            'is_estore' => true,
            'is_scene' => true,
        ),
        array(
            'id' => '0004',
            'name' => '深圳', // 22.5485150000,114.0661120000
            'image_url' => 'http://frbird.qiniudn.com/asset/160525/57456cfbfc8b12af2f8b468c-4-p750x422.jpg',
            'lng' => 114.0661120000, // 经度
            'lat' => 22.5485150000, // 纬度
            'is_estore' => true,
            'is_scene' => true,
        ),
        array(
            'id' => '0005',
            'name' => '杭州', // 30.2800590000,120.1616930000
            'image_url' => 'http://frbird.qiniudn.com/asset/160525/57456cfbfc8b12af2f8b468c-5-p750x422.jpg',
            'lng' => 120.1616930000, // 经度
            'lat' => 30.2800590000, // 纬度
            'is_estore' => true,
            'is_scene' => true,
        ),
        array(
            'id' => '0006',
            'name' => '成都', // 30.5762790000,104.0712160000
            'image_url' => 'http://frbird.qiniudn.com/asset/160525/57456cfbfc8b12af2f8b468c-6-p750x422.jpg',
            'lng' => 104.0712160000, // 经度
            'lat' => 30.5762790000, // 纬度
            'is_estore' => true,
            'is_scene' => true,
        ),
        array(
            'id' => '0007',
            'name' => '西安', // 34.3474360000,108.9463060000
            'image_url' => 'http://frbird.qiniudn.com/asset/160525/57456cfbfc8b12af2f8b468c-7-p750x422.jpg',
            'lng' => 108.9463060000, // 经度
            'lat' => 34.3474360000, // 纬度
            'is_estore' => true,
            'is_scene' => true,
        ),
        array(
            'id' => '0008',
            'name' => '武汉', // 30.5984280000,114.3118310000
            'image_url' => 'http://frbird.qiniudn.com/asset/160525/57456cfbfc8b12af2f8b468c-8-p750x422.jpg',
            'lng' => 114.3118310000, // 经度
            'lat' => 30.5984280000, // 纬度
            'is_estore' => true,
            'is_scene' => true,
        ),
    );

  /**
   * 根据省份ID显示地名
   */
  public static function show_province_name($pid){
    $str = '';
    switch((int)$pid){
      case 110000:
        $str = '北京';
        break;
      case 120000:
        $str = '天津';
        break;
      case 130000:
        $str = '河北';
        break;
      case 140000:
        $str = '山西';
        break;
      case 150000:
        $str = '内蒙古';
        break;
      case 210000:
        $str = '辽宁';
        break;
      case 220000:
        $str = '吉林';
        break;
      case 230000:
        $str = '黑龙江';
        break;
      case 310000:
        $str = '上海';
        break;
      case 320000:
        $str = '江苏';
        break;
      case 330000:
        $str = '浙江';
        break;
      case 340000:
        $str = '安徽';
        break;
      case 350000:
        $str = '福建';
        break;
      case 360000:
        $str = '江西';
        break;
      case 370000:
        $str = '山东';
        break;
      case 410000:
        $str = '河南';
        break;
      case 420000:
        $str = '湖北';
        break;
      case 430000:
        $str = '湖南';
        break;
      case 440000:
        $str = '广东';
        break;
      case 450000:
        $str = '广西';
        break;
      case 460000:
        $str = '海南';
        break;
      case 500000:
        $str = '重庆';
        break;
      case 510000:
        $str = '四川';
        break;
      case 520000:
        $str = '贵州';
        break;
      case 530000:
        $str = '云南';
        break;
      case 540000:
        $str = '西藏';
        break;
      case 610000:
        $str = '陕西';
        break;
      case 620000:
        $str = '甘肃';
        break;
      case 630000:
        $str = '青海';
        break;
      case 640000:
        $str = '宁夏';
        break;
      case 650000:
        $str = '新疆';
        break;
      case 710000:
        $str = '台湾';
        break;
      case 810000:
        $str = '香港';
        break;
      case 820000:
        $str = '澳门';
        break;
    }
    return $str;
  }

  /**
   * 根据分类类型,显示网站meta信息--传ID
   */
  public static function meta_category_id($id, $type=1){
    $category_model = new Sher_Core_Model_Category();
    $category = $category_model->extend_load((int)$id);
    if(!empty($category)){
      return self::meta_category_obj($category, $type);
    }else{
      return null;
    }
  
  }

  /**
   * 根据分类类型,显示网站meta信息--传obj
   */
  public static function meta_category_obj($obj, $type=1){
    $str = '';
    if(!empty($obj)){
      switch($type){
      //title
      case 1:
        $domain = $obj['domain'];
        //商品
        if($domain==1){
          $str = sprintf("%s-太火鸟商店-智能硬件购物第一品牌", $obj['title']);
        }elseif($domain==2){
          $str = sprintf("%s-太火鸟智能硬件社区", $obj['title']);       
        }elseif($domain==4){
          $str = sprintf("%s-智品库-太火鸟智能硬件孵化平台创新产品汇集库", $obj['title']);       
        }
        break;
      //key_words
      case 2:
        $str = $obj['tags_s'];
        break;
      // desctription
      case 3:
        $str = $obj['summary'];
        break;
      }
    }
    return $str;
  }

  /**
   *根据分类ID,显示描述信息
   */
  public static function category_desc_show($id, $type=1){
    $str = '';
    if(!empty($id)){
      switch((int)$id){
      //智创学堂
      case 59:
        $str = '智能硬件最新观点有哪些？新人小白如何创业？从“0”到“1”这里有答案！';
        break;
      //活动动态
      case 15:
        $str = '你总是需要多了解一些正在发生着的有关科技创意好玩的事。';
        break;
      // 品牌专区--产品专区
      case 21:
        $str = '新奇的想法、怪怪的想法、好想法、坏想法……有想法总比没想法强！';
        break;
      // 自由讨论-话题&吐槽
      case 27:
        $str = '只要发自内心的想去吐槽，槽点自然而然会呈现在你眼前！';
        break;
      // 产品评测-产品专区
      case 18:
        $str = '我们用专业的数据说明，让你更了解产品的优缺点';
        break;
      // 孵化需求
      case 61:
        $str = '孵化一个产品将会有数以万计的需求，缺技术？少人才？我们搭建平台，你可以在这里寻求资源与帮助。';
        break;
      // 实验室
      case 87:
        $str = '天生智能基因、以硬件为骨、软件为魄，严谨科学为师，专业分享为友，发现学术之美，在这里打磨产品，打磨自己。';
        break;
      // 智能WIFI信号扩大器
      case 121:
        $str = '生活的意义不是有几居室，而是信号有几格。';
        break;
      // 微波闪充移动电源
      case 122:
        $str = '充电10分钟，续航10小时的便携移动电源。';
        break;
      }
    }
    return $str;
  }

  /**
   * 根据渠道ID获取获取道名称
   */
  public static function fetch_channel_name($channel_id){
    $channel_name = '';
    // 渠道说明
    switch((int)$channel_id){
      case 0:
        $channel_name = '官网';
        break;
      case 10:
        $channel_name = '官网/腾讯应用宝';
        break;
      case 11:
        $channel_name = '百度手机助手';
        break;
      case 12:
        $channel_name = '360手机助手';
        break;
      case 13:
        $channel_name = '魅族商店';
        break;
      case 14:
        $channel_name = '华为应用市场';
        break;
      case 15:
        $channel_name = '搜狗手机助手';
        break;
      case 16:
        $channel_name = '中国移动MM';
        break;
      case 17:
        $channel_name = '中国电信天翼';
        break;
      case 18:
        $channel_name = '网易应用中心';
        break;
      case 19:
        $channel_name = '小米';
        break;
      case 20:
        $channel_name = '豌豆荚';
        break;
      case 21:
        $channel_name = '腾讯应用宝';
        break;
      case 22:
        $channel_name = '安智市场';
        break;
      case 23:
        $channel_name = '渠道1';
        break;
      case 24:
        $channel_name = '渠道2';
        break;
      case 25:
        $channel_name = '渠道3';
        break;
      case 26:
        $channel_name = '渠道4';
        break;
      case 27:
        $channel_name = '渠道5';
        break;
      case 28:
        $channel_name = 'oppo';
        break;
      case 29:
        $channel_name = 'vivo';
        break;
      case 30:
        $channel_name = '线下活动3';
        break;
      case 31:
        $channel_name = '线下活动4';
        break;
      case 32:
        $channel_name = '渠道6';
        break;
      case 33:
        $channel_name = '渠道7';
        break;
      case 34:
        $channel_name = '渠道8';
        break;
      case 35:
        $channel_name = '渠道9';
        break;
      case 36:
        $channel_name = '渠道10';
        break;
      case 37:
        $channel_name = '渠道11';
        break;
      case 38:
        $channel_name = '渠道12';
        break;
      case 39:
        $channel_name = '渠道13';
        break;
      case 40:
        $channel_name = '渠道14';
        break;
      case 41:
        $channel_name = '渠道15';
        break;

      default:
        $channel_name = $row['channel_id'];
    }
    return $channel_name;   
  }

}
