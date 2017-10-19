<?php
/**
 * 生成RSS ZAKER推送
 */
$config_file =  dirname(__FILE__).'/../deploy/app_config.php';
if (!file_exists($config_file)) {
    die("Can't find config_file: $config_file\n");
}
include $config_file;

define('DOGGY_VERSION',$cfg_doggy_version);
define('DOGGY_APP_ROOT',$cfg_app_deploy_root);
define('DOGGY_APP_CLASS_PATH',$cfg_app_class_path);
require $cfg_doggy_bootstrap;
require $cfg_app_rc;

set_time_limit(0);
ini_set('memory_limit','512M');

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============TRY GEN RSS WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "begin gen rss ...\n";

$topic_model = new Sher_Core_Model_Topic();
$user_model = new Sher_Core_Model_User();
$page=1;
$size=30;
$query = array('deleted'=>0, 'published'=>1, 'is_zaker_rss'=>1);
$options = array('page'=>$page,'size'=>$size,'sort'=>array('created_on'=>-1));
$list = $topic_model->find($query, $options);

$myfile = fopen("/www/phenix/web/zaker_rss.xml", "w");
$html = '';
$html .= '<?xml version="1.0" encoding="utf-8"?>'."\n";
$html .= '<rss version="2.0">'."\n";
$html .= '<channel>'."\n";
$html .= '<title>太火鸟-中国创新产品策源地</title>'."\n";
$html .= '<link>http://www.taihuoniao.com</link>'."\n";
$html .= '<description>太火鸟科技是中国创新产品策源地，是基于人工智能的产品创新引擎和智能分发SaaS平台。全力打造科技美学创新产品的新消费生态。</description>'."\n";

for($i=0;$i<count($list);$i++){
  $obj = $list[$i];
  $user = $user_model->load($obj['user_id']);
  if(Sher_Core_Helper_Util::is_mobile($user['nickname'])){
    $user['nickname'] = substr((int)$user['nickname'],0,3)."****".substr((int)$user['nickname'],7,4);
  }
  $html .= '<item>'."\n";
  $html .= sprintf("<title><![CDATA[%s]]></title>\n", $obj['title']);
  $html .= sprintf("<creator>%s</creator>\n", $user['nickname']);
  $html .= sprintf("<pubDate>%s</pubDate>\n", date(DATE_RSS, $obj['created_on']));
  $html .= sprintf("<description><![CDATA[%s]]></description>\n", htmlspecialchars_decode($obj['description']));
  $html .= sprintf("<link>%s</link>\n", Sher_Core_Helper_Url::topic_view_url($obj['_id']));
  $html .= '</item>'."\n";
  
} // end for

$html .= '</channel>'."\n";
$html .= '</rss>'."\n";

fwrite($myfile, $html);

fclose($myfile);

echo "===========================GEN RSS WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";

// sleep N minute
sleep(3600);
exit(0);
