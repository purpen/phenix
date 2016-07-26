<?php
    /**
     * 后台易购管理
     * @ author caowei@taihuoniao.com
     */
    class Sher_Admin_Action_Egou extends Sher_Admin_Action_Base {
        
        public $stash = array(
            'id' => 0,
            'page' => 1,
            'size' => 20,
        );
        
        public function execute(){
            return $this->get_list();
        }
        
        /**
         * 易购列表
         * @return string
         */
        public function get_list() {
            $this->set_target_css_state('page_egou');
            $stage = isset($this->stash['stage']) ? $this->stash['stage'] : 0;
            $pager_url = Doggy_Config::$vars['app.url.admin'].'/egou?page=#p#';
            $this->stash['pager_url'] = sprintf($pager_url, $stage);
            return $this->to_html_page('admin/egou/list.html');
        }
    }
?>
