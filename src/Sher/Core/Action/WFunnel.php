<?php
/**
 * 处理WAPI接口类方法
 * @author tianshuai.
 */
interface Sher_Core_Action_WFunnel {

  /**
   * 验证用户登录
   */
  public function verify_auth($invoke_method, $uid);

}

