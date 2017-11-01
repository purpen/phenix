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
        $desc = isset($this->stash['desc']) ? $this->stash['desc'] : '';
        $source_from = isset($this->stash['source_from']) ? $this->stash['source_from'] : '';
        $tags = isset($this->stash['tags']) ? $this->stash['tags'] : '';
        if(empty($target_id) || empty($title) || empty($content)){
            return $this->api_json('缺少请求参数！', 3001);           
        }
        Doggy_Log_helper::warn('------');
        Doggy_Log_helper::warn($title);

        $topic_model = new Sher_Core_Model_Topic();
        $topic = $topic_model->first(array('d3in_article_id'=>$target_id));
        $topic_id = 0;
        $user_id = 1;
        $category_id = 1;

        // 图片上传参数
        $token = Sher_Core_Util_Image::qiniu_token();
        $domain = Sher_Core_Util_Constant::STROAGE_TOPIC;
        $asset_type = Sher_Core_Model_Asset::TYPE_TOPIC;
        $new_file_id = new MongoId();
        $pid = (string)$new_file_id;
        $cover_id = '';
        if($cover_url){
            $imgParam = array(
                'token' => $token,
                'x:asset_type' => $asset_type,
                'x:domain' => $domain,
                'x:pid' => $pid,
                'x:user_id' => $user_id,
            );
            $cover_result = Sher_Core_Util_Image::api_upload($cover_url, $imgParam);
            Doggy_Log_helper::warn($cover_result);
        }

        $data = array(
            'title' => $title,
            'description' => $content,
            'published' => 1,
            'source' => $source_from,
            'category_id' => $category_id,
            'user_id' => $user_id,
            'tags' => $tags,
        );
        if($cover_url) {
          $data['cover_id'] = $pid;
        }
        if(empty($topic)){  // 创建
            $data['d3in_article_id'] = $target_id;
            $ok = $topic_model->apply_and_save($data);
            if($ok){
              $topic_id = $topic_model->id;
            }

        } else{   // 更新
            $data['_id'] = $topic_id = $topic['_id'];
            $ok = $topic_model->apply_and_update($data);
        }

        if(!$ok){
            return $this->api_json('同步生成失败！', 3005);        
        }

        return $this->api_json('success', 0, array('target_id'=>$topic_id));
    }

}

