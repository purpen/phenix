<?php
/**
 * 栏目管理
 * @author tianshuai
 */
class Sher_Core_Model_Column extends Sher_Core_Model_Base  {
	protected $collection = "column";
	
	protected $schema = array(
    'mark' => null,
    'name' => null,
    //备注
    'remark'  => null,
    'user_id' => 0,
    # 类型: 1.通用；2.web/wap；3.APP;
    'type' => 1,
		'status' => 1,
    # title, sub_title, cover_url, target_id, begin_time, end_time, total, sale_price, market_price, 
    'item' => array(),
  );

  protected $required_fields = array('mark', 'name');

  protected $int_fields = array('status', 'user_id', 'type');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		// 去除 html/php标签
    if(isset($row['remark'])){
          $row['strip_remark'] = strip_tags(htmlspecialchars_decode($row['remark']));
    }
	}

  /**
   * 需要高级权限才允许编辑操作的mark数组
   */
  public static function mark_safer(){
    $arr = array(
      'sign_draw_app_conf', 'sign_draw_conf', 'test',
    );
    return $arr;
  }

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {
		
		return true;
	}
	
}

