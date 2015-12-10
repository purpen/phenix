<?php
/**
 * 设置try的辅助工具
 *  @author tianshuai
 * @package default
 */
class Sher_Core_Helper_Try {

  /**
   * 试用申请黑名单查询(如加入黑名单则不能申请试用，需要联系管理员)
   */
  public static function check_try_apply_blacklist($user_id, $type=1){
    if(empty($user_id)){
      return false;
    }
    // 黑名单列表---取块内容
    $content = Sher_Core_Util_View::load_block('try_apply_blacklist', 1);
    $item_arr = array();
    if($content){
        $item_arr = explode(';', $content);
        foreach($item_arr as $item){
          $b = explode('|', $item);
            if(count($b)==2 && (int)$b[1]==(int)$user_id){
                return true;
            }
        }
    }

    return false;
  }

}
