<?php
    /**
     * 投放广告点击率及注册量统计管理
     * @author tianshuai
     */
    class Sher_Admin_Action_ThirdSiteStat extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
        
        public $stash = array(
            'id' => 0,
            'page' => 1,
            'size' => 100,
            'kind' => 0,
            'target_id' => null,
        );

        public function _init() {
          $this->set_target_css_state('page_third_site_stat');
          // 判断左栏类型
          $this->stash['show_type'] = "assist";
        }
        
        public function execute(){
            return $this->get_list();
        }
        
        /**
         * 列表
         * @return string
         */
        public function get_list() {
          $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 0;
            $pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/third_site_stat/get_list?kind=%d&target_id=%d&page=#p#', $kind, $this->stash['target_id']);

            $this->stash['pager_url'] = $pager_url;
            return $this->to_html_page('admin/third_site_stat/list.html');
        }
    }

