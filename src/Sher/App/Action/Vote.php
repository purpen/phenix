<?php
    /**
     * 前台投票管理
     * @author caowei@taihuoniao.com
     */
    class Sher_App_Action_Vote extends Sher_App_Action_Base {
        
        public $stash = array(
            'id' => 0,
            'page' => 1,
            'size' => 20,
        );
        
        protected $exclude_method_list = array('add','edit','remove');
        
        public function execute(){
            return $this->add();
        }
        
        /**
         * 投票列表
         * @return string
         */
        
        /**
         * 新增投票或编辑投票
         * @return string
         */
        public function add(){
            
            $mode = 'add';
            $id = isset($this->stash['tid']) ? (int)$this->stash['tid'] : 0;
            $tn = isset($this->stash['tn']) ? (int)$this->stash['tn'] : 1;
            
            // $n 表示投票的所属分类，这个跟vote的model里保持一致！
            switch($tn){
                case 1: // 话题
                    $model = new Sher_Core_Model_Topic();
                    break;
                default:
                    return false;
            }
            
            $data = $model->find_by_id($id);
            $this->stash['result'] = $data;
            
            return $this->to_html_page('page/vote/submit.html');
        }
        
        /**
         * 编辑投票
         * @return string
         */
        public function edit(){
            
            $mode = 'edit';
            $rid = isset($this->stash['rid']) ? (int)$this->stash['rid'] : 0;
            $vid = isset($this->stash['vid']) ? (int)$this->stash['vid'] : 0;
            $tn = isset($this->stash['tn']) ? (int)$this->stash['tn'] : 1;
            
            // $n 表示投票的所属分类，这个跟vote的model里保持一致！
            switch($tn){
                case 1: // 话题
                    $model = new Sher_Core_Model_Topic();
                    break;
                default:
                    return false;
            }
            
            $data = $model->find_by_id($rid);
            
            $model = new Sher_Core_Model_Vote();
            $vote = $model->find_votes($vid);
            
            $is_show = 0;
            if($vote){
                $is_show = 1;
            }
            
            $this->stash['result'] = $data;
            $this->stash['vote'] = $vote;
            $this->stash['mode'] = $mode;
            $this->stash['is_show'] = $is_show;
            
            return $this->to_html_page('page/vote/submit.html');
        }
        
        /**
         * 保存新增投票
         * @return string
         */
        public function save(){
            
            $is_ok = 0;
            $id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
            $rid = isset($this->stash['relevance_id']) ? (int)$this->stash['relevance_id'] : 0;
            $tn = isset($this->stash['tn']) ? (int)$this->stash['tn'] : 1;
            
            // $n 表示投票的所属分类，这个跟vote的model里保持一致！
            switch($tn){
                case 1: // 话题
                    $model = new Sher_Core_Model_Topic();
                    $redirect_url = Sher_Core_Helper_Url::topic_view_url($rid);
                    break;
                default:
                    return false;
            }
            
            $problem_date = json_decode('['.$this->stash['problem_date'].']',true);
            $problem_date = $problem_date[0];
            
            //var_dump($problem_date);die;
            try{
                
                $model_vote = new Sher_Core_Model_Vote();
                $model_problem = new Sher_Core_Model_Problem();
                $model_answer = new Sher_Core_Model_Answer();
                
                $data = array();
                $data['title'] = $this->stash['sub_title'];
                
                // 创建或更新投票
                if(empty($id)){
                    $mode = 'create';
                    $data['type'] = $tn;
                    $data['relate_id'] = $rid;
                    $data['user_id'] = $this->visitor->id;
                    $data['status'] = 1;
                    $ok = $model_vote->create($data);
                    $vid = $model_vote->id;
                }else{
                    $mode = 'edit';
                    $vid = $id;
                    $ok = $model_vote->update_set($id,$data);
                }
                if(!$ok){$is_ok++;}
                
                foreach ($problem_date as $v){
                    $data = array();
                    $data['title'] = $v['pro_title'];
                    $data['select_type'] = (int)$v['pro_type'];
                    
                    // 创建或更新问题
                    if(!isset($v['pro_id'])){
                        $data['vote_id'] = (int)$vid;
                        $ok = $model_problem->create($data);
                        $pid = $model_problem->id;
                    }else{
                        $pid = $v['pro_id'];
                        $ok = $model_problem->update_set($v['pro_id'],$data);
                    }
                    if(!$ok){$is_ok++;}
                    
                    foreach ($v['pro_answer'] as $val) {
                        $data = array();
                        $data['title'] = $val['ans_title'];
                        
                        if(!isset($val['ans_id'])){
                            $data['problem_id'] = (string)$pid;
                            $ok = $model_answer->create($data);
                            $aid = $model_answer->id;
                        }else{
                            $aid = $v['ans_id'];
                            $ok = $model_answer->update_set($val['ans_id'],$data);
                        }
                        if(!$ok){$is_ok++;}
                    }
                }
                
                // 将提交的项目关联id加入相对应的表
                if(empty($id)){
                    $date = array('vote_id' => (int)$vid);
                    if(!$model->update_set($rid,$date)){
                        $is_ok++;
                    }
                }
                
                if($is_ok){
                    return $this->ajax_json('保存失败,请重新提交', true);
                }
            }catch(Sher_Core_Model_Exception $e){
                Doggy_Log_Helper::warn("Save block failed: ".$e->getMessage());
                return $this->ajax_json('保存失败:'.$e->getMessage(), true);
            }
            
            return $this->ajax_json('保存成功.', false, $redirect_url);
        }
        
        /**
        * 删除投票
        */
        public function deleted(){
           
            $id = isset($this->stash['vid']) ? (int)$this->stash['vid'] : 0;
            $rid = isset($this->stash['rid']) ? (int)$this->stash['rid'] : 0;
            $tn = isset($this->stash['tn']) ? (int)$this->stash['tn'] : 1;
            
            if(empty($id)){
               return $this->ajax_notification('投票信息不存在！', true);
            }
            
            // $n 表示投票的所属分类，这个跟vote的model里保持一致！
            switch($tn){
                case 1: // 话题
                    $model = new Sher_Core_Model_Topic();
                    break;
                default:
                    return false;
            }
            
           $ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
           
           try{
                $vote_model = new Sher_Core_Model_Vote();
                foreach($ids as $id){
                    $result = $vote_model->vote_remove((int)$id);
                    if(!$result){
                        return $this->ajax_notification('删除数据失败！', true);
                    }
                    
                    // 将提交的项目关联id加入相对应的表
                    $date = array(
                        'vote_id' => 0
                    );
                    if(!$model->update_set($rid,$date)){
                        return $this->ajax_notification('更新据失败！', true);
                    }
                }
           }catch(Sher_Core_Model_Exception $e){
               return $this->ajax_notification('操作失败,请重新再试', true);
           }
           
           return $this->to_taconite_page('ajax/reload.html');
        }
        
        /**
        * ajax删除投票问题
        */
        public function del_problem(){
            
            $id = isset($this->stash['id']) ? $this->stash['id'] : 0;
            
            if(empty($id)){
               echo 0;
            }
            
            try{
                $model = new Sher_Core_Model_Problem();
                $result = $model->problem_remove($id);
                if($result){
                    echo 1;
                }else{
                    echo 0;
                }
           }catch(Sher_Core_Model_Exception $e){
               return $this->ajax_notification('操作失败,请重新再试', true);
           }
        }
        
        /**
        * ajax删除投票答案
        */
        public function del_answer(){
            
            $id = isset($this->stash['id']) ? $this->stash['id'] : 0;
            
            if(empty($id)){
               echo 0;
            }
            
            try{
                $model = new Sher_Core_Model_Answer();
                $result = $model->answer_remove($id);
                if($result){
                    echo 1;
                }else{
                    echo 0;
                }
           }catch(Sher_Core_Model_Exception $e){
               return $this->ajax_notification('操作失败,请重新再试', true);
           }
        }
    }
?>
