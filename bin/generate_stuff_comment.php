#!/usr/bin/env php
<?php
/**
 * 自动生成评论
 */
$config_file =  dirname(__FILE__).'/../deploy/app_config.php';
if (!file_exists($config_file)) {
    die("Can't find config_file: $config_file\n");
}

include $config_file;

define('DOGGY_VERSION', $cfg_doggy_version);
define('DOGGY_APP_ROOT', $cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH', $cfg_app_class_path);

require $cfg_doggy_bootstrap;
@require 'autoload.php';
@require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

echo "stuff add comment ...\n";

$comment_list = array(
  '124' => array( // 智能家居
    '没想到现在有这么多的家居类的智能产品',
    '这么好的智能家居产品，能先送套房吗....',
    '才几年的功夫，智能家居马上就成为了现实',
    '还有没有更牛掰点的',
    '能凑够100个智能产品也好不容易啊',
    '妈妈们可以考虑入手一个',
    '送老人挺好的',
    '不知道实际体验如何',
    '家人用起来方便才好',
  
  ),
  '125' => array( // 智慧办公
    '这些东西配齐，上班也是件享受的事儿',
    '这些东西也就办公比较实用点',
    '感觉以后的这些东西都要放到一张办公桌里了',
    '办公智能化大大提高办公效率，感觉大脑都不够用了',
    '办公室的好伴侣，真不错。',
    '售价多少呢？',
    '便携性怎么样',
  ),
  '126' => array( // 情趣生活
    '都快放弃要找女盆友的想法了',
  ),
  '127' => array( // 智能户外
    '看了这些好东西，想励志做个出行达人了',
    '出行都让人越来越懒，这个世界变化太大了',
    '好想配齐一整套旅行神器',
    '我看上了你，但是没钱',
    '出行都艺术得不要不要的',
  ),
  '128' => array( // 亲情呵护
    '我小时候要有这些好东西就好了',
    '帅哥美女从胎儿做起',
    '现在当妈越来月省事了',
    '小孩健康，从胎儿抓起',
  ),
  '129' => array( // 宠物关爱
    '瞬间觉得活得不如狗',
  ),
  '130' => array( // 智能车载
    '努力买辆车，再逛太火鸟',
    '该为自己的爱车配置点东西了',
    '那么多车载的产品我竟然都不知道',
    '剁手不再是女人的专利',
    '智能让出行更安全',
  ),
  '131' => array( // 时尚穿戴
    '穿戴产品火了这么久，现在新品比较少了',
    '感觉智能穿戴还缺少点本质上的变革',
    '只是把手机上能做的事情搬到手表上，算是创新吗？',
    '智能穿戴+虚拟现实可能才是发展的方向',
  ),
  '132' => array( // 休闲娱乐
    '下一个娱乐的领域一定是虚拟现实',
    '太火鸟有VR的产品吗',
    '太火鸟商城里有卖吗', 
  ),
  '133' => array( // 医疗健康
    '用了这东西会不会长寿啊',
    '可以考虑给爸妈买一个',
    '智能健康看着挺唬人，不知道好用不',
    '好用的话可以入手',
    '价格，我要知道价格',
    '购买链接，小编能发一下吗',
  ),
  '0' => array( // 全部
  
  ),
);

/**
 * 测试环境
$comment_list = array(
  '21' => array( // 休闲娱乐
    '下一个娱乐的领域一定是虚拟现实',
    '太火鸟有VR的产品吗',
    '太火鸟商城里有卖吗', 
  ),
  '22' => array( // 医疗健康
    '用了这东西会不会长寿啊',
    '可以考虑给爸妈买一个',
    '智能健康看着挺唬人，不知道好用不',
    '好用的话可以入手',
    '价格，我要知道价格',
    '购买链接，小编能发一下吗',
  ),
  '23' => array( // 智能户外
    '看了这些好东西，想励志做个出行达人了',
    '出行都让人越来越懒，这个世界变化太大了',
    '好想配齐一整套旅行神器',
    '我看上了你，但是没钱',
    '出行都艺术得不要不要的',
  ),
);
 */

$common_comment_list = array(
  '设计不错哦',
  '不知道上手体验如何',
  '真好，我要买',
  '好厉害！！！',
  '我要搞一台！',
  '颜色很漂亮',
  '有其他颜色吗？',
  '这个我喜欢',
  '太牛了，喜欢',
  '看起来不错哦',
  '就是辣么高端大气上档次。',
  '长知识了！',
  '这个不错！！',
  '炒鸡给力的东东',
  '期待此产品！！！！',
  '很好奇，这个怎么卖？',
  '哪里能买到呢？',
  '好简约漂亮！',
  '好活动，赞一个！',
  '请问这个在哪里可以买到？',
  '样子非常不错',
  '就想知道这个东西具体怎么用',
  '图片看着真牛逼',
  '看起来萌萌哒',
  '不知道续航时间怎么样',
  '看到自己喜欢的产品，必须顶一个',
  '这个东西会不会上太火鸟试用啊？',
  '东西不错，就是价格有点高',
  '这个东西太实用了，一会就去买个',
  '有人用过这个东西么？',
  '终于看到了自己喜欢的产品了',
  '看起来不错哦',
  '的确是非常好的产品，值得拥有',
  '不知道使用起来复杂不复杂',
  '这个产品是安卓和苹果的设备都能用么？',
  '我是吴彦祖的粉丝，我是来捧场的',
  '女神我来支持你了',
  '活动棒棒哒',
  '我是打酱油的~',
  '这个厉害啊！',
  '看上去就很想拥有一个',
  '价格低些就好了',
  '这么高大上，真想上手体验下',
  '这个东西碉堡了！！！',
  '看上去科技感十足，可以有机会试用么？',
  '这个东西在太火鸟可以购买到么？',
  '这个是哪个公司的产品？怎么查看联系方式？',
  '我是一家数码店的代理，想了解下这个产品更多资料',
  '请问可以代理这款产品么？有什么条件？',
  '如果进货的话，价格大概是多少？',
  '我想代理这个产品，售后和发货是怎么样的？',
  '现在这科技真是发达',
  '产品不错，赞一个',
  '科技范十足啊',
  '设计感不错',
  '有了这个确实好很多',
  '好酷炫的产品',
  '在报道里见过 貌似还不错',
  '好想试试 有没有试用啊',
  '这个设计真的很用心',
  '好厉害的设计',
  '好东西好想要一个',
  '东西不错，有线下售卖吗',
  '好东西，哪里有卖',
  '想买一个送人',
  '第一眼就爱上这个产品了',
  '支持楼主，有试用没',
  '喜欢喜欢~',
  '这个貌似颠覆了传统？',
  '看起来不错哦',
  '好创意，这个有市场。',
  '不错不错！',
  '的确是非常好的产品，值得拥有',
  '样子看着不错。',
  'cool~~~ ',
  '好厉害的样子',
  '别拦我 我要剁手',
  '长见识，支持',
  '主要看的是气质。',
  '在哪购买啊？？？',
  '创意十足啊！！！ ',
  '这产品很实用',
  '有头脑，就有灵感，便有了创意，赶上市场需求。',
  '这么高大上，真想上手玩玩',
  '不错啊感觉很好 ',
  '赞一个，非常不错、！',
  '不错，很有艺术范 ',
  'V5霸气有一套！ ',
  '非常精美哦。 ',
  '不知道玩起来怎么样',
  '效果好吗？',
  '这个东西安全性怎么样',
  '大家都怎么看这个产品',
  '这个产品有没有专利',
  '我记得有个类似的产品，有人知道是哪个吗？',
  '这个价格比想象的有点贵啊',
  '简单的事情做的不简单，平凡的事情做的不平凡！',
  '赞一个。活动圆满 ',
  '不论结局怎样，有期待还是好的',
  '产品怎么样，用了才知道，快点给我一个吧！',
  '看介绍很有吸引力呀',
  '期待评选结果',
  '哈哈，不知道这些都是什么，不过好高大上的样子',
  // 负页
  '这么烂的东西也能上来？',
  '虽然没用过，但感觉一般般',
  '样子太丑了，也不知道怎么设计出来的',
  '感觉像圈钱的产品',
  '这个东西我用过，太垃圾了',
  '个人感觉产品意义不大',
  '感觉用户体验不好！',
  '没发现这产品亮点在哪里',
  '不太实用啊，很少有人真正需要它。',

);


$user_page = 1;
$mark = sprintf("user_list_0%d", $user_page);
$user_list_arr = Sher_Core_Util_View::fetch_user_list($mark, $user_page);
if(empty($user_list_arr)){
  echo "user list is empty!\n";
  exit;
}

$pid = Doggy_Config::$vars['app.stuff.top100_category_id'];
$category_model = new Sher_Core_Model_Category();
$categories = $category_model->find(array('domain'=>4, 'pid'=>$pid, 'is_open'=>Sher_Core_Model_Category::IS_OPENED));
foreach($categories as $k=>$v){
  $c_var = sprintf("category_count_%d", $v['_id']);
  $$c_var = 0;
}

$stuff_model = new Sher_Core_Model_Stuff();
$comment_model = new Sher_Core_Model_Comment();
$page = 1;
$size = 2000;
$is_end = false;
$total = 0;
while(!$is_end){
  $time = 0;
	$query = array();
  $query['fid'] = $pid;
  $query['from_to'] = 5;
	$options = array('field' => array('_id', 'title', 'category_id', 'from_to', 'fid', 'love_count', 'created_on'), 'sort'=>array('love_count'=>-1), 'page'=>$page,'size'=>$size);
	$list = $stuff_model->find($query, $options);
	if(empty($list)){
		echo "get stuff list is null,exit......\n";
		break;
	}
	$max = count($list);
	for ($i=0; $i < $max; $i++) {
    $id = $list[$i]['_id'];
    $love_count = $list[$i]['love_count'];
    $title = $list[$i]['title'];
    $category_id = (string)$list[$i]['category_id'];
    $from_to = $list[$i]['from_to'];
    $c_var = sprintf("category_count_%d", (int)$category_id);


    $user_index = array_rand($user_list_arr, 1);
    $user_id = (int)$user_list_arr[$user_index];
    if(empty($user_id)){
      echo "user_id is null! \n";
      continue;
    }

		$row = array();
		$row['user_id'] = $user_id;
		$row['target_id'] = (string)$id;
		$row['type'] = 6;
    $row['sub_type'] = $from_to;
		$row['from_site'] = 1;

    if(isset($comment_list[$category_id]) && isset($comment_list[$category_id][$$c_var])){
      echo "is category comment...\n";
      $row['content'] = $comment_list[$category_id][$$c_var];

      $$c_var += 1;
    }else{
      $comment_index = array_rand($common_comment_list, 1);
      $row['content'] = $common_comment_list[$comment_index];
      echo "is common comment...\n";
    }

    try{
      //$ok = $comment_model->create($row);
      $ok = true;
      if($ok){
        sleep(10);
        $total++;
        echo "stuff title: $title, love_count: $love_count.\n";
        echo "comment content: ".$row['content']."\n";
      }else{
        echo "comment save error.\n";
      }
    }catch(Exception $e){
      echo "comment save is error: ".$e->getMessage()."\n";
    }
    
	}
	if($max < $size){
		break;
	}
	$page++;
	echo "page [$page] updated---------\n";
}

echo "stuff add comment count: $total is OK! \n";

