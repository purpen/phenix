<?php
    /**
     * 投放广告点击率及注册量统计管理
     * @author tianshuai
     */
    class Sher_Admin_Action_ThirdSiteStat extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
        
        public $stash = array(
            'id' => 0,
            'page' => 1,
            'size' => 20,
        );

        public function _init() {
          $this->set_target_css_state('page_third_site_stat');
        }
        
        public function execute(){
            return $this->get_list();
        }
        
        /**
         * 列表
         * @return string
         */
        public function get_list() {
            $pager_url = Doggy_Config::$vars['app.url.admin'].'/third_site_stat?page=#p#';
            $this->stash['pager_url'] = $pager_url;
            return $this->to_html_page('admin/third_site_stat/list.html');
        }
    }

