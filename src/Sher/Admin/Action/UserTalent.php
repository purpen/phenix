<?php
    /**
     * 达人认证管理
     * @ author caowei@taihuoniao.com
     */
    class Sher_Admin_Action_UserTalent extends Sher_Admin_Action_Base {
        
        public $stash = array(
            'page' => 1,
            'size' => 100,
            'verified' => null,
        );
        
        public function execute(){
            // 判断左栏类型
            $this->stash['show_type'] = "user";
            return $this->get_list();
        }
        
        /**
         * 达人认证列表
         * @return string
         */
        public function get_list() {
            $this->set_target_css_state('page_user_talent');
            $pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/user_talent?verified=%d&page=#p#', $this->stash['verified']);
            $this->stash['pager_url'] = sprintf($pager_url);
            return $this->to_html_page('admin/user_talent/list.html');
        }
        
        /**
         * 达人认证
         */
        public function ajax_verified(){
            
            $id = isset($this->stash['id']) ? $this->stash['id'] : 0;
            $evt = isset($this->stash['evt']) ? (int)$this->stash['evt'] : 0;
            
            if(!$id){
                return $this->ajax_json('内容不存在！', true);
            }
            
            $model = new Sher_Core_Model_UserTalent();
            $user_model = new Sher_Core_Model_User();
            
            $ok = $model->mark_as_verified($id, $evt);
            if(!$ok){
                return $this->ajax_json('审核失败！', true);
            }

            return $this->to_taconite_page('admin/user_talent/approved_ok.html');
        }
    }

