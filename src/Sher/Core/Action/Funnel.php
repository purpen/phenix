<?php
/**
 * 处理API接口类方法
 * @author purpen.
 */
interface Sher_Core_Action_Funnel {

  /**
   * 验证用户登录
   */
  public function check_current_user($invoke_method, $current_user_id);

}

