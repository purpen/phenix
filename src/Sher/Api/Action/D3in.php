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
        $target_user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
        if(empty($target_id) || empty($title) || empty($content)){
            return $this->api_json('缺少请求参数！', 3001);           
        }

        $topic_model = new Sher_Core_Model_Topic();
        $asset_model = new Sher_Core_Model_Asset();
        $topic = $topic_model->first(array('d3in_article_id'=>$target_id, 'deleted'=>0));
        $topic_id = 0;
        $user_id = Doggy_Config::$vars['app.d3ingo_article_synchro']['user_id'];
        switch($target_user_id){
            case 28:  // 魏星
              $user_id = 1158986;
              break;
            case 2:
              $user_id = 20448;
              break;
            default: // 马哲
              $user_id = Doggy_Config::$vars['app.d3ingo_article_synchro']['user_id'];
        }
        $category_id = Doggy_Config::$vars['app.d3ingo_article_synchro']['category_id'];
        $asset_id = 0;
        $cover_id = '';

        if($content){
            $Parsedown = new Parsedown();
            $content = $Parsedown->text($content);
            // 个性化匹配
            $content = preg_replace('/::: (hljs-left|hljs-center|hljs-right).*\n((.|\n)*?).*:::.*\n/', '<div class="$1">$2</div>',$content);
            // Doggy_Log_Helper::warn($content);
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

        if(empty($topic)){  // 创建
            if($cover_url){
                // 创建附件
                $b_file = @file_get_contents($cover_url);
                $arr = Sher_Core_Util_Image::image_info_binary($b_file);
                if($arr['stat'] != 0){
                    $asset_model->set_file_content($b_file);

                    $img_info['size'] = strlen($b_file);
                    $img_info['mime'] = Doggy_Util_File::mime_content_type($cover_url);
                    $img_info['filename'] = basename($cover_url).'.'.strtolower($arr['format']);
                    $img_info['filepath'] = Sher_Core_Util_Image::gen_path($cover_url, 'topic');
                    $img_info['asset_type'] = Sher_Core_Model_Asset::TYPE_TOPIC;
                    $img_info['width'] = $arr['width'];
                    $img_info['height'] = $arr['height'];
                    $img_info['format'] = $arr['format'];
        
                    // Doggy_Log_Helper::warn(json_encode($img_info));
                    $asset_ok = $asset_model->apply_and_save($img_info);
                    if($asset_ok){
                        $asset_id = (string)$asset_model->id;
                        $data['cover_id'] = $asset_id;
                    }
                }
            }

            $data['d3in_article_id'] = $target_id;
            $ok = $topic_model->apply_and_save($data);
            if($ok) {
                $topic_id = $topic_model->id;
                // 更新附件关联ID
                if($asset_id) $asset_model->update_set($asset_id, array('parent_id' => $topic_id));
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

