<?php
/**
 * 处理API接口类方法
 * @author purpen.
 */
interface Sher_Core_Action_WFunnel {

  /**
   * 验证用户登录
   */
  public function verify_auth($invoke_method, $current_user_id);

}

