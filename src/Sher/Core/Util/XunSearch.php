<?php
/**
 * 迅搜全文索引工具类
 * @author tianshuai
 */
class Sher_Core_Util_XunSearch {
    
    const DB_PHENIX = 'phenix';

  public function __construct() {
    echo 'aaaaaaaaaaaa';exit;
  }
	
	/**
	 * 验证索引索引服务器是否启动
	 */
	public static function check_stat() {

	}

  /**
   * 添加文档
   */
  public static function add($data=array(), $db='phenix') {

    try{
      $xs = new \XS($db);
      $index = $xs->index; // 获取 索引对象
      // 创建文档对象
      $doc = new XSDocument;
      $doc->setFields($data);
   
      // 添加到索引数据库中
      $index->add($doc);

    }catch(XSException $e){
      Doggy_Log_Helper::warn('add:'.$e->getTraceAsString(), 'search');
    }

  }

  /**
   * 更新文档
   */
  public static function update($data=array(), $db='phenix') {
    try{
      $xs = new \XS($db);
      $index = $xs->index; // 获取 索引对象
      // 创建文档对象
      $doc = new XSDocument;
      $doc->setFields($data);
   
      // 更新到索引数据库中
      $ok = $index->update($doc);
      if($ok){
        return array('success'=>true, 'msg'=>'操作成功!');
      }else{
        return array('success'=>false, 'msg'=>'操作失败!');   
      }
    }catch(XSException $e){
      Doggy_Log_Helper::warn('update:'.$e->getTraceAsString(), 'search');
      return array('success'=>false, 'msg'=>'操作失败: '.$e->getTraceAsString());
    }

  }

  /**
    * 删除文档
    * ID可为数组
   */
  public static function del_ids($ids, $db='phenix') {
    try{
      $xs = new \XS($db);
      $index = $xs->index; // 获取 索引对象
   
      // 删除
      $ok = $index->del($ids);
      if($ok){
        return array('success'=>true, 'msg'=>'操作成功!');
      }else{
        return array('success'=>false, 'msg'=>'删除失败!');       
      }

    }catch(XSException $e){
      Doggy_Log_Helper::warn('delete:'.$e->getTraceAsString(), 'search');
      return array('success'=>false, 'msg'=>'删除失败!'.$e->getTraceAsString());    
    }

  }

  /**
   * 搜索
   */
  public static function search($str, $options=array(), $db='phenix') {
    if(empty($str)){
      return array('success'=>false, 'msg'=>'搜索内容为空!');
    }
    $str_f = $str;
    $page = isset($options['page'])?(int)$options['page']:1;
    $size = isset($options['size'])?(int)$options['size']:50;
    $sort = isset($options['sort'])?(int)$options['sort']:0;
    $asc = isset($options['asc'])?(boolean)$options['asc']:false;

    $evt = isset($options['evt'])?(string)$options['evt']:'content';
    $t = isset($options['t'])?(string)$options['t']:0;

    try{
      $xs = new \XS($db); // 建立 XS 对象，项目名称为：demo
      $search = $xs->search; // 获取 搜索对象

      //类型
      if($t){
        switch($t){
          case 1:
            $str_f = sprintf('kind:Product cid:9 %s', $str_f);
            break;
          case 5:
            $str_f = sprintf('kind:Product cid:1 %s', $str_f);
            break;
          case 2:
            $str_f = sprintf('kind:Topic %s', $str_f);
            break;
          case 4:
            $str_f = sprintf('kind:Stuff %s', $str_f);
            break;
            
        }
      }


      //是否搜索标签
      if($evt=='tag'){
        $str_f = sprintf('tags:%s ', $str_f);
      }else{
        $search->addWeight('title', $str); // 增加附加条件：提升标题中包含 'xunsearch' 的记录的权重       
      }

      $search->setQuery($str_f); // 设置搜索语句


      //排序
      if(!empty($sort)){
        if($sort==1){
          $search->setSort('created_on', $asc); // 最新
        }elseif($sort==2){
          $search->setSort('updated_on', $asc); // 更新
        }
      }

      $current_per = ($page-1)*$size;
      $search->setLimit($size, $current_per); // 设置返回结果最多为 5 条，并跳过前 10 条
   
      $docs = $search->search(); // 执行搜索，将搜索结果文档保存在 $docs 数组中
      $count = $search->count(); // 获取搜索结果的匹配总数估算值
      $data = array();
      $user_model = new Sher_Core_Model_User();
      foreach($docs as $k=>$v){
        $data[$k]['pid'] = $v['pid'];
        $data[$k]['oid'] = $v['oid'];
        $data[$k]['tid'] = $v['tid'];
        $data[$k]['cid'] = $v['cid'];
        $data[$k]['kind'] = $v['kind'];
        $data[$k]['title'] = $v['title'];
        $data[$k]['content'] = strip_tags(htmlspecialchars($v['content']));
        $data[$k]['user_id'] = $v['user_id'];
        $data[$k]['tags'] = !empty($v['tags'])?explode(',', $v['tags']):array();
        $data[$k]['created_on'] = $v['created_on'];
        $data[$k]['updated_on'] = $v['updated_on'];
        $data[$k]['high_title'] = $search->highlight($v->title); // 高亮处理 title 字段
        $data[$k]['high_content'] = htmlspecialchars_decode($search->highlight($v->content)); // 高亮处理 content 字段
        switch($v['kind']){
          case 'Stuff':
            $data[$k]['view_url'] = Sher_Core_Helper_Url::stuff_view_url($v['oid']);
            break;
          case 'Topic':
            $data[$k]['view_url'] = Sher_Core_Helper_Url::topic_view_url($v['oid']);
            break;
          case 'Product';
            $data[$k]['view_url'] = self::gen_view_url($v['cid'], $v['oid']);
            break;
          default:
            $data[$k]['view_url'] = '#';
        }

        // 获取用户信息
        if($v['user_id']){
          $user = $user_model->find_by_id((int)$v['user_id']);
          $data[$k]['nickname'] = $user['nickname'];
          $data[$k]['home_url'] = Sher_Core_Helper_Url::user_home_url($user['_id']);
        }

        // 获取asset_type
        $data[$k]['asset_type'] = self::gen_asset_type($v['kind']);

      }

      $result = array('success'=>true, 'data'=>$data, 'data_count'=>$count, 'msg'=>'success');
      return $result;
    }catch(XSException $e){
      Doggy_Log_Helper::warn('search:'.$e->getTraceAsString(), 'search');
      return array('success'=>false, 'msg'=>'搜索异常!');
    }

  }

	/**
	 * 获取产品不同阶段的URL
	 */
	public static function gen_view_url($stage, $id){
		$stage = isset($stage) ? (int)$stage : 0;
		switch($stage) {
			case Sher_Core_Model_Product::STAGE_VOTE:
				$view_url = Sher_Core_Helper_Url::vote_view_url($id);
				break;
			case Sher_Core_Model_Product::STAGE_PRESALE:
				$view_url = Sher_Core_Helper_Url::sale_view_url($id);
				break;
			case Sher_Core_Model_Product::STAGE_SHOP:
				$view_url = Sher_Core_Helper_Url::shop_view_url($id);
				break;
			case Sher_Core_Model_Product::STAGE_EXCHANGE:
				$view_url = Sher_Core_Helper_Url::shop_view_url($id);
				break;
			default:
				$view_url = Doggy_Config::$vars['app.url.fever'];
		}
		
		return $view_url;
	}

	/**
	 * 根据类型获取图片asset_type
	 */
	public static function gen_asset_type($kind){
		switch($kind) {
			case 'Topic':
				$asset_type = 55;
				break;
			case 'Stuff':
				$asset_type = 70;
				break;
			case 'Product':
				$asset_type = 10;
				break;
			default:
				$asset_type = 0;
		}
		
		return $asset_type;
	}

	
}

