<?php
/**
 * 专题管理
 * @author tianshuai
 */
class Sher_Admin_Action_Special extends Sher_Admin_Action_Base implements DoggyX_Action_Initialize {
	
	public $stash = array(
		'page' => 1,
		'size' => 20,
	);
	
	public function _init() {
		$this->set_target_css_state('page_special');
    }
	
	/**
	 * 入口
	 */
	public function execute() {
		// 判断左栏类型
		$this->stash['show_type'] = "community";
		return $this->get_list();
	}
	
	/**
	 * 列表
	 */
	public function get_list() {
    $this->set_target_css_state('page_all');
		$page = (int)$this->stash['page'];
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/special?page=#p#');
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/special/list.html');
	}

  /**
   * 名单 SubjectRecord
   */
  public function get_attend_list(){
		$page = (int)$this->stash['page'];
    $this->stash['target_id'] = isset($this->stash['target_id'])?$this->stash['target_id']:0;
    $this->stash['event'] = isset($this->stash['event'])?$this->stash['event']:1;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/special/get_attend_list?target_id=%s&event=%s&page=#p#', $this->stash['target_id'], $this->stash['event']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/special/attend_list.html');
  }

  /**
   * 名单 Attend
   */
  public function get_visitor_list(){
		$page = (int)$this->stash['page'];
    $this->stash['target_id'] = isset($this->stash['target_id'])?$this->stash['target_id']:0;
    $this->stash['event'] = isset($this->stash['event'])?$this->stash['event']:1;
		
		$pager_url = sprintf(Doggy_Config::$vars['app.url.admin'].'/special/get_visitor_list?target_id=%s&event=%s&page=#p#', $this->stash['target_id'], $this->stash['event']);
		
		$this->stash['pager_url'] = $pager_url;
		
		return $this->to_html_page('admin/special/visitor_list.html');
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

		$model = new Sher_Core_Model_SubjectRecord();
    $user_model = new Sher_Core_Model_User();

    $subject_record = $model->load($id);
    if(!$subject_record){
 			return $this->ajax_notification('数据不存在！', true);   
    }
		$result = $model->mark_as_state((string)$id, $state);
    if($result['status']){
      $this->stash['success'] = true;
      $target_id = $result['target_id'];
      $number = isset($result['number'])?$result['number']:0;
      //通过后生成号码
      if($state==1 && empty($number)){
        $is_exist_random = false;
        while(!$is_exist_random){
          $number = rand(100000, 999999);
          $has_one = $model->first(array('target_id'=>$target_id, 'number'=>$number));
          $is_exist_random = !empty($has_one)?false:true;
        }
        $this->stash['number'] = $number;
        $ok = $model->update_set((string)$id, array('number' => $number));     
      }
      
      //京东众筹-短信私信提醒
      if($target_id==3){
        $user = $user_model->find_by_id((int)$result['user_id']);
        if(!empty($user)){
          //短信提醒
          if(!empty($user['profile']['phone'])){
            if($state==1 && !empty($number)){
              $msg = "恭喜您，您报名的 中国智能硬件蛋年创新大会·深圳站 已经通过审核，您的票号为 $number,太火鸟诚邀您出席。时间: 2015年5月16日18:00；地点: 深圳·中芬产业园·花样年福年广场。更多详情：http://dwz.cn/JsnBw";
            }elseif($state==2){
              $msg = "亲爱的会员您好，我们很遗憾的通知您，由于报名人数过多，会场空间有限，您报名的 中国智能硬件蛋年创新大会·深圳站 未通过审核，更多精彩活动请关注：http://dwz.cn/ISlKP";
            }
            // 开始发送
            //$message = Sher_Core_Helper_Util::send_defined_mms($user['profile']['phone'], $msg);
          }

          //私信提醒
          if(!empty($user['profile']['phone'])){
            if($state==1 && !empty($number)){
              $msg = "恭喜您，您报名的“2015京东众筹BIGGER大会”已经通过审核，您的票号：".$number."，太火鸟诚邀您出席。时间：2015/5/7-14:00；地点：海上五号棚·上海徐汇区漕溪北路595号上海电影广场.";
            }elseif($state==2){
              $msg = "亲爱的会员您好：我们很抱歉的通知您，由于报名人数过多，会场空间有限，您报名的“2015京东众筹BIGGER大会”未能通过审核。更多精彩活动请关注太火鸟官网";
            }
			      //$message = new Sher_Core_Model_Message();
            //$message->send_site_message($msg, $this->visitor->id, $user['_id']);
          }
          
        }
      }

      //金投赏-短信私信提醒
      if($target_id==5){
        if(isset($subject_record['info']['phone']) && !empty($subject_record['info']['phone'])){
          $user_phone = $subject_record['info']['phone'];
          //短信提醒
          if(!empty($user_phone)){
            if($state==1 && !empty($number)){
              $msg = "您好，您已通过报名申请，您的票号是: ".$number."， 请于10月22日13:30，准时参加位于：上海市南京西路1376号波特曼四层大宴会厅的《金投赏产品创意趋势论坛》。——[金投赏]";
            }elseif($state==2){
              $msg = "对不起，由于名额已满，您的报名申请没有通过，感谢支持，请您继续关注我们的活动。——[金投赏]";
            }
            // 开始发送
            $message = Sher_Core_Helper_Util::send_defined_mms($user_phone, $msg);
          }
          
        }
      }

      //京东造逆-短信私信提醒
      if($target_id==6){
        if(isset($subject_record['info']['phone']) && !empty($subject_record['info']['phone'])){
          $user_phone = $subject_record['info']['phone'];
          //短信提醒
          if(!empty($user_phone)){
            if($state==1 && !empty($number)){
              $msg = "您已通过报名申请，邀请码是[$number]，请于11月14日（周六）13:30，凭借邀请函或短信邀请码入场。地点: 北京中关村创业大街京东智能奶茶馆。[ 逆·造 ]";
            }elseif($state==2){
              $msg = "对不起，由于名额已满，您没有报名成功，感谢支持，请您继续关注我们的活动。[ 逆·造 ]";
            }
            // 开始发送
            $message = Sher_Core_Helper_Util::send_defined_mms($user_phone, $msg);
          }
          
        }
      }

      //京东bigger2-短信私信提醒
      if($target_id==7){
        if(isset($subject_record['info']['phone']) && !empty($subject_record['info']['phone'])){
          $user_phone = $subject_record['info']['phone'];
          //短信提醒
          if(!empty($user_phone)){
            if($state==1 && !empty($number)){
              $msg = "您好，您已通过报名！请于11月26日14:00（周四）凭借手机号码准时参加由京东众筹主办，太火鸟协办的“创X造”京东众筹BIGGER大会（深圳站），非常感谢！地址：深圳市万科前海国际会议中心一层大宴会厅";
              // 开始发送
              $message = Sher_Core_Helper_Util::send_defined_mms($user_phone, $msg);
            }elseif($state==2){
              //$msg = "";
            }

          }
          
        }
      }

    }else{
 		  $this->stash['success'] = false;   
    }
    $this->stash['message'] = $result['msg'];
    $this->stash['state'] = $state;

		return $this->to_taconite_page('admin/special/ajax_state.html');

  }

	/**
	 * 导出参与列表
	 */
	public function export(){
		$query = array();
		$options = array();
		$page = 1;
		$size = 500;

    $query['target_id'] = (int)$this->stash['target_id'];
    //$query['state'] = isset($this->stash['state'])?(int)$this->stash['state']:0;
		
		if(empty($query)){
			return $this->ajax_json('请选择导出数据条件！', true);
		}
		
		// 设置不超时
		set_time_limit(0);
			
		 header('Content-Type: application/vnd.ms-excel');
		 header('Content-Disposition: attachment;filename="data.csv"');
		 header('Cache-Control: max-age=0');
		
    //Windows下使用BOM来标记文本文件的编码方式 -解决windows下乱码
    //fwrite($export_file, chr(0xEF).chr(0xBB).chr(0xBF)); 
		// 打开PHP文件句柄，php://output表示直接输出到浏览器
     $fp = fopen('php://output', 'a');

    	// Windows下使用BOM来标记文本文件的编码方式 
    	fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// 输出Excel列名信息
		$head = array('ID', '编号', '姓名', '电话', '公司', '职位', '地址', '状态');
		foreach($head as $i => $v){
			// CSV的Excel支持GBK编码，一定要转换，否则乱码
			// $head[$i] = iconv('utf-8', 'gbk', $v);
		}
		// 将数据通过fputcsv写到文件句柄
		fputcsv($fp, $head);
		
		$service = Sher_Core_Service_SubjectRecord::instance();
		
		$is_end = false;
		$counter = 0;
		$limit = 1000;
        $options['size'] = $size;
		$options['sort_field'] = 'latest';
		
		while(!$is_end){
			$options['page'] = $page;
			
			$result = $service->get_all_list($query, $options);
			
			$max = count($result['rows']);
			for($i=0; $i<$max; $i++){
				$counter ++;
				if($limit == $counter){
					ob_flush();
					flush();
					$counter = 0;
				}
				
        $data = $result['rows'][$i];
        $user_id = $data['user_id'];
        $number = isset($data['number'])?$data['number']:0;
        $realname = isset($data['info']['realname'])?$data['info']['realname']:'--';
        $phone = isset($data['info']['phone'])?$data['info']['phone']:'--';
        $company = isset($data['info']['company'])?$data['info']['company']:'--';
        $job = isset($data['info']['job'])?$data['info']['job']:'--';
        $address = isset($data['info']['address'])?$data['info']['address']:'--';
        $state = $data['state'];
        if($state==0){
          $stat_str = '未审核';
        }elseif($state==1){
          $stat_str = '通过';       
        }elseif($state==2){
          $stat_str = '拒绝';       
        }
				
				$row = array($user_id, $number, $realname, $phone, $company, $job, $address, $stat_str);
				
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

	/**
	 * 删除参与记录 subject_record
	 */
	public function del_attend(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('ID不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_SubjectRecord();
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
	 * 删除参与记录 attend
	 */
	public function del_visitor(){
		$id = $this->stash['id'];
		if(empty($id)){
			return $this->ajax_notification('ID不存在！', true);
		}
		
		$ids = array_values(array_unique(preg_split('/[,，\s]+/u', $id)));
		
		try{
			$model = new Sher_Core_Model_Attend();
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

}
