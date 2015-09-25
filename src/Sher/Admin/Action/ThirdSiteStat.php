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
            // 统计点击数量
            $dig_model = new Sher_Core_Model_DigList();
            $dig_key = Sher_Core_Util_Constant::DIG_THIRD_SITE_STAT;

            $dig = $dig_model->load($dig_key);
            if(!empty($dig) || isset($dig['items']["stat_1"])){
              $this->stash['site_360'] = $dig['items']['stat_1'];
            }else{
              $this->stash['site_360'] = 0;
            }
            $this->stash['pager_url'] = $pager_url;
            return $this->to_html_page('admin/third_site_stat/list.html');
        }
    }

