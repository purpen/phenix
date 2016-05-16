<?php
/**
 * 区块
 * @author purpen
 */
class Sher_Core_Model_Block extends Sher_Core_Model_Base  {
	protected $collection = "block";
	
	protected $schema = array(
    'mark' => null,
    'title' => null,
    'code' => null,
    'content' => null,
    //备注
    'remark'  => null,
    'user_id' => 0,
    # 类型: 1.通用；2.web/wap；3.APP;
    'kind' => 1,
    'pack' => null,
		'state' => 1,
  	);

  protected $required_fields = array('mark', 'title');

  protected $int_fields = array('state', 'user_id', 'kind');


	/**
	 * 扩展数据
	 */
	protected function extra_extend_model_row(&$row) {
		// HTML 实体转换为字符
		if (isset($row['content'])){
			$row['content'] = htmlspecialchars_decode($row['content']);
		}
		// 去除 html/php标签
        if(isset($row['remark'])){
              $row['strip_remark'] = strip_tags(htmlspecialchars_decode($row['remark']));
        }
        
        if(isset($row['kind'])){
            switch($row['kind']){
              case 1:
                $row['kind_label'] = '通用';
                break;
              case 2:
                $row['kind_label'] = 'Web/Wap';
                break;
              case 3:
                $row['kind_label'] = 'APP';
                break;
              default:
                $row['kind_label'] = '--';
            }
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

