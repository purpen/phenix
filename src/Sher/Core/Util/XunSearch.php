<?php
/**
 * 迅搜全文索引工具类
 * @author tianshuai
 */
class Sher_Core_Util_XunSearch {
    
    const DB_PHENIX = 'phenix';

public function __construct() {
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
    $db = Doggy_Config::$vars['app.xun_search_db'];
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
    $db = Doggy_Config::$vars['app.xun_search_db'];
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
    $db = Doggy_Config::$vars['app.xun_search_db'];
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
    $db = Doggy_Config::$vars['app.xun_search_db'];
    if(empty($str)){
      return array('success'=>false, 'msg'=>'搜索内容为空!');
    }
    $str_f = $str;
    $page = isset($options['page'])?(int)$options['page']:1;
    $size = isset($options['size'])?(int)$options['size']:5;
    $sort = isset($options['sort'])?(int)$options['sort']:0;
    $asc = isset($options['asc'])?(boolean)$options['asc']:false;

    $evt = isset($options['evt'])?(string)$options['evt']:'content';
    $t = isset($options['t'])?(string)$options['t']:0;
    $oid = isset($options['oid'])?(string)$options['oid']:0;
    $type = isset($options['type'])?(int)$options['type']:0;

    try{
      $xs = new \XS($db); // 建立 XS 对象，项目名称为：demo
      $search = $xs->search; // 获取 搜索对象
      $condition = '';
      //类型
      if($t){
        switch($t){
          case 1:
            $condition .= 'kind:Product (cid:9 OR cid:5 OR cid:12 OR cid:15) ';
            $str_f = sprintf('%s%s', $condition, $str);
            break;
          case 5:
            $condition .= 'kind:Product cid:1 ';
            $str_f = sprintf('%s%s', $condition, $str);
            break;
          case 2:
            $condition .= 'kind:Topic ';
            $str_f = sprintf('%s%s', $condition, $str);
            break;
          case 4:
            $condition .= 'kind:Stuff ';
            $str_f = sprintf('%s%s', $condition, $str);
            break;
        }
      }

      //用于相关搜索,过滤当前结果
      if($type){
        switch($type){
          case 1:
            $condition .= sprintf("-oid:%s ", $oid);
            break;
          case 2:
            break;
        }
      }

      //是否搜索标签
      if($evt=='tag'){
        $tag_arr = explode(',', $str);
        $x_tag_arr = array();
        foreach($tag_arr as $v){
          array_push($x_tag_arr, sprintf("tags:%s", $v));
        }
        $x_tag_str = implode(' OR ', $x_tag_arr);
        $str_f = sprintf('%s(%s)', $condition, $x_tag_str);
      }else{
        $search->addWeight('title', $str); // 增加附加条件：提升标题中包含 关键字 的记录的权重       
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
      $count = $search->count(); // 获取搜索结果的匹配总数估算值 /放在search()之后,优化查询
      //页码数
      $total_page = ceil($count/$size);
      $data = array();

      foreach($docs as $k=>$v){
        $data[$k]['pid'] = $v['pid'];
        $data[$k]['oid'] = $v['oid'];
        $data[$k]['tid'] = $v['tid'];
        $data[$k]['cid'] = $v['cid'];
        $data[$k]['kind'] = $v['kind'];
        $data[$k]['title'] = $v['title'];
        $data[$k]['cover_id'] = $v['cover_id'];
        $data[$k]['content'] = $v['content'];
        $data[$k]['user_id'] = $v['user_id'];
        $data[$k]['tags'] = !empty($v['tags'])?explode(',', $v['tags']):array();
        $data[$k]['created_on'] = $v['created_on'];
        $data[$k]['updated_on'] = $v['updated_on'];
        $data[$k]['high_title'] = $search->highlight($v->title); // 高亮处理 title 字段
        $data[$k]['high_content'] = htmlspecialchars_decode($search->highlight($v->content)); // 高亮处理 content 字段

      }

      $result = array('success'=>true, 'data'=>$data, 'total_count'=>$count, 'total_page'=>$total_page, 'msg'=>'success');
      return $result;
    }catch(XSException $e){
      Doggy_Log_Helper::warn('search:'.$e->getTraceAsString(), 'search');
      return array('success'=>false, 'msg'=>'搜索异常!');
    }

  }

	
}

