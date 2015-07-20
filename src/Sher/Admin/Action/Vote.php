<?php
    /**
     * 后台投票管理
     * @author caowei@taihuoniao.com
     */
    class Sher_Admin_Action_Vote extends Sher_Admin_Action_Base {
        
        public $stash = array(
            'id' => 0,
            'page' => 1,
            'size' => 20,
        );
        
        public function execute(){
            return $this->get_list();
        }
        
        /**
         * 投票列表
         * @return string
         */
        public function get_list() {
            $stage = isset($this->stash['stage']) ? $this->stash['stage'] : 0;
            $this->set_target_css_state('page_vote');
            $pager_url = Doggy_Config::$vars['app.url.admin'].'/vote?page=#p#';
            $this->stash['pager_url'] = sprintf($pager_url, $stage);
            return $this->to_html_page('admin/vote/list.html');
        }
        
        /**
         * 新增投票或编辑投票
         * @return string
         */
        public function add(){
            return $this->to_html_page('admin/vote/submit.html');
        }
        
        /**
         * 编辑投票
         * @return string
         */
        public function edit(){
            
            $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
            $mode = 'edit';
            
            $model = new Sher_Core_Model_Vote();
            $vote = $model->find_votes($id);
            
            $this->stash['vote'] = $vote;
            $this->stash['mode'] = $mode;
            
            return $this->to_html_page('admin/vote/submit.html');
        }
        
        /**
         * 保存新增投票
         * @return string
         */
        public function save(){
            
            $is_ok = 0;
            $id = (int)$this->stash['id'];
            $page = (int)$this->stash['page'];
            
            $problem_date = json_decode('['.$this->stash['problem_date'].']',true);
            //var_dump($problem_date);die;
            try{
                
                $model_vote = new Sher_Core_Model_Vote();
                if(empty($id)){
                    $mode = 'create';
                    $data = array();
                    $data['title'] = $this->stash['sub_title'];
                    $data['relate_id'] = $this->stash['relevance_id'];
                    $data['status'] = 1;
                    $ok = $model_vote->create($data);
                    if($ok){
                        $vid = $model_vote->id;
                        $model_problem = new Sher_Core_Model_Problem();
                        foreach ($problem_date[0] as $v){
                            $data = array();
                            $data['title'] = $v['pro_title'];
                            $data['select_type'] = $v['pro_type'];
                            $data['vote_id'] = $vid;
                            $ok = $model_problem->create($data);
                            if($ok){
                                $pid = $model_problem->id;
                                $model_answer = new Sher_Core_Model_Answer();
                                foreach ($v['pro_answer'] as $val) {
                                    $data = array();
                                    $data['title'] = $val;
                                    $data['problem_id'] = (string)$pid;
                                    $ok = $model_answer->create($data);
                                    if(!$ok){
                                        $is_ok++;
                                    }
                                }
                            }else{
                                $is_ok++;
                            }
                        }
                    }else{
                        $is_ok++;
                    }
                    
                    if($is_ok){
                        return $this->ajax_json('保存失败,请重新提交', true);
                    }
                }else{
                    $mode = 'edit';
                    $data = array();
                    $data['title'] = (string)$this->stash['sub_title'];
                    $data['relate_id'] = (int)$this->stash['relevance_id'];
                    $data['status'] = 1;
                    $ok = $model_vote->update_set($id,$data);
                    if($ok){
                        $model_problem = new Sher_Core_Model_Problem();
                        $result = $model_problem->find(array("vote_id"=>(int)$id));
                        foreach($result as $v){
                            if(!$model_problem->problem_remove($v['_id'])){
                               $is_ok++;
                            }
                        }
                        
                        if($is_ok){
                            return $this->ajax_json('保存失败,请重新提交', true);
                        }
                        
                        foreach ($problem_date[0] as $v){
                            $data = array();
                            $data['title'] = $v['pro_title'];
                            $data['select_type'] = $v['pro_type'];
                            $data['vote_id'] = $id;
                            $ok = $model_problem->create($data);
                            if($ok){
                                $pid = $model_problem->id;
                                $model_answer = new Sher_Core_Model_Answer();
                                foreach ($v['pro_answer'] as $val) {
                                    $data = array();
                                    $data['title'] = $val;
                                    $data['problem_id'] = (string)$pid;
                                    $ok = $model_answer->create($data);
                                    if(!$ok){
                                        $is_ok++;
                                    }
                                }
                            }else{
                                $is_ok++;
                            }
                        }
                        
                        if($is_ok){
                            return $this->ajax_json('保存失败,请重新提交', true);
                        }
                    }
                }
            }catch(Sher_Core_Model_Exception $e){
                Doggy_Log_Helper::warn("Save block failed: ".$e->getMessage());
                return $this->ajax_json('保存失败:'.$e->getMessage(), true);
            }
            
            $redirect_url = Doggy_Config::$vars['app.url.admin'].'/vote?page='.$page;
            return $this->ajax_json('保存成功.', false, $redirect_url);
        }
        
        /**
        * 删除投票
        */
        public function deleted(){
           
           $id = isset($this->stash['id']) ? $this->stash['id'] : 0;
           if(empty($id)){
               return $this->ajax_notification('投票信息不存在！', true);
           }
           
           $ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
           
           try{
                $model = new Sher_Core_Model_Vote();
                foreach($ids as $id){
                    $result = $model->vote_remove((int)$id);
                    if(!$result){
                         return $this->ajax_notification('删除数据失败！', true);
                    }
                }
           }catch(Sher_Core_Model_Exception $e){
               return $this->ajax_notification('操作失败,请重新再试', true);
           }
           
           $this->stash['ids'] = $ids;
           return $this->to_taconite_page('ajax/delete.html');
        }
    }
?>
