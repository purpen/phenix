<?php
/**
 * 举报/投诉
 * @author tianshuai
 */
class Sher_Core_Model_ReportTip extends Sher_Core_Model_Base  {
	
    protected $collection = "report";

    //投票
    const T_TYPE_VOTE = 1;
    const T_TYPE_STUFF = 2;
    const T_TYPE_SCENE = 3;
    const T_TYPE_SIGHT = 4;
    const T_TYPE_USER = 5;
    
    // 设备来源
    
    const FORM_WEB_SOURCE = 1;
    const FORM_WAP_SOURCE = 2;
    const FORM_IOS_SOURCE = 3;
    const FORM_ANDROID_SOURCE = 4;
    const FORM_IPAD_SOURCE = 5;
    
    // 应用来源
    const APP_WEB_SOURCE = 1;
    const APP_SHOP_SOURCE = 2;
    const APP_FIU_SOURCE = 3;

    //类型: 1,举报
    const KIND = 1;
	
    protected $schema = array(
        // 举报内容关联ID
        'target_id' => null,
        // 举报类型
        'target_type' => self::T_TYPE_VOTE,
        // 被举报用户ID
        'target_user_id' => 0,
        'title' => null,
        // 描述
        'content' => null,
        //备注
        'remark'  => null,
        // 举报人ID
        'user_id' => 0,
        // 类型:1,举报;
        'kind'  => 1,
        //举报原因:1,色情报力；2.盗图；3.广告/欺诈信息;4.－－
        'evt' => 1,
        // 设备来源
        'from_to' => self::FORM_WEB_SOURCE,
        // 应用来源
        'application' => self::APP_WEB_SOURCE,
        // 是否处理
		'status' => 0,
  	);

    protected $required_fields = array('target_id', 'user_id','from_to','application');

    protected $int_fields = array('status', 'user_id', 'kind', 'evt', 'target_type', 'target_user_id', 'from_to','application');

    protected $joins = array(
        'user'  => array('user_id'  => 'Sher_Core_Model_User'),
    );

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

    // 举报目标
    switch($row['target_type']){
      case 1:
        $row['target_type_label'] = '投票';
        break;
      case 2:
        $row['target_type_label'] = '灵感';
        break;
      case 3:
        $row['target_type_label'] = '情景';
        break;
      case 4:
        $row['target_type_label'] = '场景';
        break;
      case 5:
        $row['target_type_label'] = '用户';
        break;
      default:
        $row['target_type_label'] = '--';
    }

    // 举报原因
    switch($row['evt']){
      case 1:
        $row['evt_label'] = '色情报力';
        break;
      case 2:
        $row['evt_label'] = '盗图';
        break;
      case 3:
        $row['evt_label'] = '广告/欺诈信息';
        break;
      default:
        $row['evt_label'] = '--';
    }

    // 来源
    if(isset($row['from_to'])){
      switch($row['evt']){
        case 1:
          $row['from_label'] = 'Web';
          break;
        case 2:
          $row['from_label'] = 'Wap';
          break;
        case 3:
          $row['from_label'] = 'IOS';
          break;
        case 4:
          $row['from_label'] = 'Android';
          break;
        case 5:
          $row['from_label'] = 'iPad';
          break;
        default:
          $row['from_label'] = '--';
      }   
    }

    // 应用
    if(isset($row['application'])){
      switch($row['application']){
        case 1:
          $row['application_label'] = '网站';
          break;
        case 2:
          $row['application_label'] = '商城APP';
          break;
        case 3:
          $row['application_label'] = 'Fiu';
          break;
        default:
          $row['application_label'] = '--';
      }   
    }
		
	}

	/**
	 * 删除后事件
	 */
	public function mock_after_remove($id) {

	}

  /**
   * 状态操作
   */
  public function mark_as_stat($id, $status=1){
    $data = $this->extend_load((string)$id);

    if(empty($data)){
        return array('status'=>0, 'msg'=>'内容不存在');
    }
    if($data['status']==(int)$status){
        return array('status'=>0, 'msg'=>'重复的操作');  
    }
    $ok = $this->update_set((string)$id, array('status' => $status));
    if($ok){
        return array('status'=>1, 'msg'=>'操作成功');  
    }else{
        return array('status'=>0, 'msg'=>'操作失败');   
    }
  }
	
}
