<?php
/**
 * API D3IN 接口
 * @author tianshuai
 */
class Sher_Api_Action_D3in extends Sher_Api_Action_Base {
	
    protected $filter_user_method_list = "*";

    /**
     * 入口
     */
    public function execute(){
        return $this->product_list();
    }


    /**
     * 同步D3IN文章
     */
    public function synchro_article(){
        $target_id = isset($this->stash['id']) ? (int)$this->stash['id'] : 0;
        $title = isset($this->stash['title']) ? $this->stash['title'] : '';
        $cover_url = isset($this->stash['cover_url']) ? $this->stash['cover_url'] : '';
        $content = isset($this->stash['content']) ? $this->stash['content'] : '';
        if(empty($target_id) || empty($title) || empty($content)){
            return $this->api_json('缺少请求参数！', 3001);           
        }
        Doggy_Log_helper::warn('------');
        Doggy_Log_helper::warn($title);

        $topic_model = new Sher_Core_Model_Topic();
        $topic = $topic_model->first(array('d3in_article_id'=>$target_id));
        $topic_id = 0;
        if(empty($topic)){  // 创建

        } else{   // 更新
        
        }

        return $this->api_json('success', 0, array('target_id'=>$topic_id));
    }

}

