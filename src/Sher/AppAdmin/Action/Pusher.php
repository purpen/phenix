<?php
/**
 * app推送管理
 * @author tianshuai
 */
class Sher_AppAdmin_Action_Pusher extends Sher_AppAdmin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 100,
		'uuid' => '',
		'from_to' => '',
		'is_login' => '',
		'user_id' => '',
		'state' => '',
    'channel_id' => '',
	);
	
	public function _init() {
		$this->set_target_css_state('page_app');
		$this->stash['show_type'] = "app";
  }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $this->set_target_css_state('page_all');
		$page = (int)$this->stash['page'];
		
		$pager_url = Doggy_Config::$vars['app.url.app_admin'].'/pusher/get_list?is_login=%d&from_to=%d&user_id=%d&uuid=%s&state=%d&channel_id=%d&page=#p#';

		$this->stash['pager_url'] = sprintf($pager_url, $this->stash['is_login'], $this->stash['from_to'], $this->stash['user_id'], $this->stash['uuid'], $this->stash['state'], $this->stash['channel_id']);
		
		return $this->to_html_page('app_admin/pusher/list.html');
	}
	
	/**
	 * 创建/更新
	 */
	public function submit(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:0;

	}

	/**
	 * 保存信息
	 */
	public function save(){		

	}

	/**
	 * 删除
	 */
	public function deleted(){
		$id = isset($this->stash['id'])?(string)$this->stash['id']:0;
		if(empty($id)){
			return $this->ajax_notification('评论不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Pusher();
			
			foreach($ids as $id){
				$pusher = $model->load($id);
				
        if (!empty($pusher)){
          //逻辑删除
					$model->remove((string)$id);
				}
			}
			
			$this->stash['ids'] = $ids;
			
		}catch(Sher_Core_Model_Exception $e){
			return $this->ajax_notification('操作失败,请重新再试', true);
		}
		
		return $this->to_taconite_page('ajax/delete.html');
	}

  /**
   * 导出用户资料
   */
  public function export(){
  
		$query = array();
		$options = array();
		$page = 1;
		$size = 500;

    $channel_id = isset($this->stash['channel_id']) ? (int)$this->stash['channel_id'] : 0;
    $from_to = isset($this->stash['from_to']) ? (int)$this->stash['from_to'] : 0;
    $is_login = isset($this->stash['is_login']) ? (int)$this->stash['is_login'] : 0;
    $sort = isset($this->stash['sort']) ? (int)$this->stash['sort'] : 0;

    if($channel_id){
      $query['channel_id'] = $channel_id;   
    }

    if($is_login){
      if($is_login==-1){
        $query['is_login'] = 0;
      }elseif($is_login==1){
        $query['is_login'] = 1;
      }
    }

    if($from_to){
      $query['from_to'] = $from_to;
    }

    $options['size'] = $size;
		// 排序
		switch ($sort) {
			case 0:
				$options['sort_field'] = 'latest';
				break;
			case 1:
				$options['sort_field'] = 'last_time';
				break;
		}
		
		// 设置不超时
		set_time_limit(0);
			
		 header('Content-Type: application/vnd.ms-excel');
		 header('Content-Disposition: attachment;filename="user_info.csv"');
		 header('Cache-Control: max-age=0');
		
    //Windows下使用BOM来标记文本文件的编码方式 -解决windows下乱码
    //fwrite($export_file, chr(0xEF).chr(0xBB).chr(0xBF)); 
		// 打开PHP文件句柄，php://output表示直接输出到浏览器
     $fp = fopen('php://output', 'a');

    	// Windows下使用BOM来标记文本文件的编码方式 
    	fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 输出Excel列名信息
		$head = array('手机号', '创建时间');
		foreach($head as $i => $v){
			// CSV的Excel支持GBK编码，一定要转换，否则乱码
			// $head[$i] = iconv('utf-8', 'gbk', $v);
		}
		// 将数据通过fputcsv写到文件句柄
		fputcsv($fp, $head);
		
		$service = Sher_Core_Service_Pusher::instance();
		
		$is_end = false;
		$counter = 0;
		$limit = 1000;
		
		while(!$is_end){
			$options['page'] = $page;
			
			Doggy_Log_Helper::warn("Export try apply page[$page],size[$size]!");
			
			$result = $service->get_pusher_list($query, $options);
			
			$max = count($result['rows']);
			for($i=0; $i<$max; $i++){
				$counter ++;
				if($limit == $counter){
					ob_flush();
					flush();
					$counter = 0;
				}
				
        $pusher = $result['rows'][$i];
				$row = array($pusher['user']['account'], date('Y-m-d H:i:s', $pusher['created_on']));
				
				/*
				foreach($row as $k => $v){
					// CSV的Excel支持GBK编码，一定要转换，否则乱码
					// $row[$i] = iconv('utf-8', 'gbk', $v);
				}*/
				
				fputcsv($fp, $row);
				
				unset($row);
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

