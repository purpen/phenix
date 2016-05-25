<?php
    /**
     * 达人认证管理
     * @ author caowei@taihuoniao.com
     */
    class Sher_Admin_Action_UserTalent extends Sher_Admin_Action_Base {
        
        public $stash = array(
            'page' => 1,
            'size' => 20,
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
            $pager_url = Doggy_Config::$vars['app.url.admin'].'/user_talent?page=#p#';
            $this->stash['pager_url'] = sprintf($pager_url);
            return $this->to_html_page('admin/user_talent/list.html');
        }
        
        /**
         * 达人认证
         */
        public function ajax_approved(){
            
            $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
            
            if(!$id){
                return $this->ajax_json('内容不存在！', true);
            }
            
            $model = new Sher_Core_Model_UserTalent();
            $user_model = new Sher_Core_Model_User();
            
            $ok = $model->update_set($id,array('verified'=>1));
            if(!$ok){
                return $this->ajax_json('审核失败！', true);
            }
            $res = $model->first($id);
            $ok = $user_model->update_set($res['user_id'],array('verified'=>1));
            if(!$ok){
                return $this->ajax_json('修改用户表信息失败！', true);
            }
            return $this->to_taconite_page('admin/user_talent/approved_ok.html');
        }
    }

