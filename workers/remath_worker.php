<?php
/**
 * 汇总
 * @author purpen
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
require $cfg_app_rc;

// composer support
require 'autoload.php';

use Mailgun\Mailgun;

set_time_limit(0);
ini_set('memory_limit','512M');

date_default_timezone_set('Asia/shanghai');

echo "-------------------------------------------------\n";
echo "===============REMATH WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Start to remath...\n";

try{
    $diglist = new Sher_Core_Model_DigList();
    
    // 计算投票创意数量
    $product = new Sher_Core_Model_Product();
    $total_count = $product->count(array(
        'stage'=>Sher_Core_Model_Product::STAGE_VOTE
    ));
    $vote_count  = $product->count(array(
        'stage'=>Sher_Core_Model_Product::STAGE_VOTE, 
        'voted_finish_time'=>array(
            '$gt' => time(),
        )
    ));
    // 更新数量
    $criteral = array('_id' => Sher_Core_Util_Constant::FEVER_COUNTER);
    $some_data = array(
        'items' => array(
            'total_count' => $total_count,
            'vote_count'  => $vote_count,
        )
    );
    $diglist->update_set($criteral, $some_data, true, false, true);
    
}catch(Sher_Core_Model_Exception $e){
    echo "Remath product failed: ".$e->getMessage();
}

echo "===========================REMATH WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";
// sleep 600 seconds
sleep(600);
exit(0);