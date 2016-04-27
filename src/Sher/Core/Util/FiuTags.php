<?php
/**
 * 情景标签
 * @author tianshuai
 */
class Sher_Core_Util_FiuTags extends Doggy_Exception {
	
	/**
   * 通过ID数组返回标签字符串
   * $tags 标签数组ＩＤ；
   * $sep: 是否分隔字符串: 输入分隔符或直接返回数组
   * $relation 是否加载相关标签
	 */
	public static function fetch_tag_str($tags, $sep=null, $relation=true){
    if(!is_array($tags) || empty($tags)){
      return array();
    }
    $scene_tags_model = new Sher_Core_Model_SceneTags();
    $new_tags = array();
    for($i=0;$i<count($tags);$i++){
      $tag = $scene_tags_model->load((int)$tags[$i]);
      if(empty($tag)){
        continue;
      }
      array_push($new_tags, $tag['title_cn']);
      if($relation){
        if(isset($tag['likename']) && is_array($tag['likename']) && !empty($tag['likename'])){
          array_merge($new_tags, $tag['likename']);
        }
      }

    } // endfor

    if($sep){
      return implode($sep, $new_tags); 
    }
		return $new_tags;
	}

	
}

