<?php
/**
 * 活动抽奖管理
 * @author tianshuai
 */
class Sher_Admin_Action_ActiveDrawRecord extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
        'target_id' => 0,
        'day' => '',
        'user_id' => '',
        'event' => '',
        'state' => '',
        'ip' => '',
        'kind' => 0,
        'start_date' => '',
        'end_date' => '',
        'from_to' => 0,
	);
	
	public function _init() {
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		$this->set_target_css_state('page_active_draw_record');
    }
	
	/**
	 * 入口
	 */
	public function execute() {

		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 0;
    switch($kind){
      case 0:
        $this->set_target_css_state('all');
        break;
      case 1:
        $this->set_target_css_state('page');
        break;
      case 2:
        $this->set_target_css_state('app');
        break;
    }
    $this->set_target_css_state('page_all');
    $this->stash['target_id'] = isset($this->stash['target_id'])?$this->stash['target_id']:0;
    $this->stash['event'] = isset($this->stash['event'])?$this->stash['event']:0;

      $start_time = strtotime($this->stash['start_date']);
	  $end_time = strtotime($this->stash['end_date']);    

		$this->stash['start_time'] = $start_time;
		$this->stash['end_time'] = $end_time;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/active_draw_record?day=%d&user_id=%d&state=%d&event=%d&target_id=%d&ip=%s&kind=%d&start_date=%s&end_date=%s&page=#p#', $this->stash['day'], $this->stash['user_id'], $this->stash['state'], $this->stash['event'], $this->stash['target_id'], $this->stash['ip'], $kind, $this->stash['start_date'], $this->stash['end_date'], $this->stash['from_to']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/active_draw_record/list.html');
	}

  /**
   * 通过/拒绝
   */
  public function ajax_state(){
    $id = $this->stash['id'];
    $state = isset($this->stash['state'])?(int)$this->stash['state']:0;
    if(empty($id)){
			return $this->ajax_notification('缺少参数！', true);
    }

		$model = new Sher_Core_Model_ActiveDrawRecord();

    $draw_sign_record = $model->load($id);
    if(!$draw_sign_record){
 			return $this->ajax_notification('数据不存在！', true);   
    }
		$ok = $model->update_set($id, array('state'=>$state));
    if(!$ok){
			return $this->ajax_notification('操作失败！', true);  
    }
    $this->stash['success'] = true;
    $this->stash['state'] = $state;

		return $this->to_taconite_page('admin/active_draw_record/ajax_state.html');
  }

	/**
	 * 删除
	 */
	public function deleted(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('ID不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_ActiveDrawRecord();
			foreach($ids as $id){
				$record = $model->load($id);
				// 
				if ($record){
					$model->remove($id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

  /**
   * 记录发货信息
   */
  public function ajax_record_desc(){
    $sid = isset($this->stash['sid']) ? $this->stash['sid'] : null;
    $desc = isset($this->stash['desc']) ? $this->stash['desc'] : null;
    $state = isset($this->stash['state']) ? (int)$this->stash['state'] : 0;
    if(empty($sid) || empty($desc)){
      return $this->ajax_json('缺少请求参数!', true);
    }

		$model = new Sher_Core_Model_ActiveDrawRecord();
    $ok = $model->update_set($sid, array('desc'=>$desc, 'state'=>$state));
    if(!$ok){
      return $this->ajax_json("发送失败!", true);
    }

    return $this->ajax_json("发送成功!", false, null, $sid);
  
  }

	/**
	 * 导出中奖名单
	 */
	public function export(){
    $user_id = isset($this->stash['user_id']) ? (int)$this->stash['user_id'] : 0;
    $target_id = isset($this->stash['target_id']) ? (int)$this->stash['target_id'] : 0;
    $event = isset($this->stash['event']) ? (int)$this->stash['event'] : 0;
    $kind = isset($this->stash['kind']) ? (int)$this->stash['kind'] : 1;
    $state = isset($this->stash['state']) ? (int)$this->stash['state'] : 0;
    $day = isset($this->stash['day']) ? (int)$this->stash['day'] : 0;
    $ip = isset($this->stash['ip']) ? $this->stash['ip'] : null;

		$query = array();
    $options = array();

		$page = 1;
		$size = 500;

    if ($user_id) {
        $query['user_id'] = (int)$user_id;
    }
    if ($target_id) {
         $query['target_id'] = (int)$target_id;         
    }
    if ($event) {
      if((int)$event==-1){
        $query['event'] = 0;         
      }else{
        $query['event'] = (int)$event;
      }
    }
    if ($kind) {
      $query['kind'] = (int)$kind;
    }
    if ($state) {
      if((int)$state==-1){
        $query['state'] = 0;
      }else{
        $query['state'] = (int)$state;
      }
    }
    if ($day) {
      $query['day'] = (int)$day;
    }
    if($ip){
      $query['ip'] = $ip;
    }
		
		if(empty($query)){
			return $this->ajax_json('请选择导出数据条件！', true);
		}
		
		// 设置不超时
		set_time_limit(0);
			
		 header('Content-Type: application/vnd.ms-excel');
		 header('Content-Disposition: attachment;filename="draw_list.csv"');
		 header('Cache-Control: max-age=0');
		
    //Windows下使用BOM来标记文本文件的编码方式 -解决windows下乱码
    //fwrite($export_file, chr(0xEF).chr(0xBB).chr(0xBF)); 
		// 打开PHP文件句柄，php://output表示直接输出到浏览器
     $fp = fopen('php://output', 'a');

    	// Windows下使用BOM来标记文本文件的编码方式 
    	fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 输出Excel列名信息
		$head = array('ID', '期数', '奖品', '姓名', '电话', '地址', '邮编', '是否处理');
		foreach($head as $i => $v){
			// CSV的Excel支持GBK编码，一定要转换，否则乱码
			// $head[$i] = iconv('utf-8', 'gbk', $v);
		}
		// 将数据通过fputcsv写到文件句柄
		fputcsv($fp, $head);
		
		$service = Sher_Core_Service_ActiveDrawRecord::instance();
		
		$is_end = false;
		$counter = 0;
		$limit = 1000;
        $options['size'] = $size;
		$options['sort_field'] = 'latest';
		
		while(!$is_end){
			$options['page'] = $page;
			
			Doggy_Log_Helper::warn("Export sign draw list page[$page],size[$size]!");
			
			$result = $service->get_sign_draw_record_list($query, $options);
			
			$max = count($result['rows']);
			for($i=0; $i<$max; $i++){
				$counter ++;
				if($limit == $counter){
					ob_flush();
					flush();
					$counter = 0;
				}
				
        $obj = $result['rows'][$i];
        $username = isset($obj['receipt']['name']) ? $obj['receipt']['name'] : '--';
        $userphone = isset($obj['receipt']['phone']) ? $obj['receipt']['phone'] : '--';
        if(isset($obj['receipt'])){
          $address = sprintf("%s-%s-%s", $obj['receipt']['province'], $obj['receipt']['district'], $obj['receipt']['address']);
        }else{
          $address = '--';
        }
				$zip = isset($obj['receipt']['zip']) ? $obj['receipt']['zip'] : '--';
        $state = empty($obj['state']) ? '否' : '是'; 
				$row = array((string)$obj['_id'], $obj['target_id'], $obj['title'], $username, $userphone, $address, $zip, $state);
				
				/*
				foreach($row as $k => $v){
					// CSV的Excel支持GBK编码，一定要转换，否则乱码
					// $row[$i] = iconv('utf-8', 'gbk', $v);
				}*/
				
				fputcsv($fp, $row);
				
				unset($row);
				unset($user);
			}
			
			if($max < $size){
				$is_end = true;
				break;
			}
			
			$page++;
		}
		
		fclose($fp);

	}

}
