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
    $xs = new \XS($db);
    $index = $xs->index; // 获取 索引对象
    // 创建文档对象
    $doc = new XSDocument;
    $doc->setFields($data);
 
    // 添加到索引数据库中
    $index->add($doc);
  }

  /**
   * 更新文档
   */
  public static function update($data=array(), $db='phenix') {
    $xs = new \XS($db);
    $index = $xs->index; // 获取 索引对象
    // 创建文档对象
    $doc = new XSDocument;
    $doc->setFields($data);
 
    // 更新到索引数据库中
    $index->update($doc);
  }

  /**
    * 删除文档
    * ID可为数组
   */
  public static function del_ids($ids, $db='phenix') {
    $xs = new \XS($db);
    $index = $xs->index; // 获取 索引对象
 
    // 删除
    $index->del($ids);
  }

  /**
   * 搜索
   */
  public static function search($str, $kind=0, $options=array(), $db='phenix') {
    if(empty($str)){
      return false;
    }
    $page = isset($options['page'])?(int)$options['page']:1;
    $size = isset($options['size'])?(int)$options['size']:50;
    $sort = isset($options['sort'])?$options['sort']:'created_on';
    $desc = isset($options['desc'])?$options['desc']:false;

    $xs = new \XS($db); // 建立 XS 对象，项目名称为：demo
    $search = $xs->search; // 获取 搜索对象

    $search->setQuery($str); // 设置搜索语句
    //$search->addWeight('subject', 'xunsearch'); // 增加附加条件：提升标题中包含 'xunsearch' 的记录的权重
    //$search->setSort($sort, $desc); // 排序

    $current_per = ($page-1)*$size;
    $search->setLimit($size, $current_per); // 设置返回结果最多为 5 条，并跳过前 10 条
 
    $docs = $search->search(); // 执行搜索，将搜索结果文档保存在 $docs 数组中
    //$count = $search->count(); // 获取搜索结果的匹配总数估算值
    return $docs;

  }

	
}

