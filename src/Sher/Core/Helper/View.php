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
        $stash['js_sher_bundle_version'] = Doggy_Config::$vars['app.version.sher_bundle_version'];
        $stash['css_use_bundle'] = Doggy_Config::$vars['app.css.use_bundle'];
        $stash['css_bundle_version'] = Doggy_Config::$vars['app.version.css_bundle_version'];
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

        $stash['site_menu_admin_report_unsolved'] = Sher_Core_Helper_Url::build_url_path('app.url.admin','report','state','unsolved');
        $stash['site_menu_admin_report_solved'] = Sher_Core_Helper_Url::build_url_path('app.url.admin','report','state','solved');
        $stash['site_menu_admin_reply_unsolved'] = Sher_Core_Helper_Url::build_url_path('app.url.admin','reply','state','unsolved');
        $stash['site_menu_admin_reply_solved'] = Sher_Core_Helper_Url::build_url_path('app.url.admin','reply','state','solved');

    }

}
?>