#!/usr/bin/env php
<?php
/**
 * 初始化积分子系统
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

echo "Prepare to init point type...\n";

$model = new Sher_Core_Model_PointType();

$point_types = Doggy_Config::$vars['app.point.init_point_types'];
foreach ($point_types as $point_type) {
    $model->create($point_type);
    echo "add point type:" . $model->id . " code:".$model->code."\n";
}

echo "Prepare to init user ranks...\n";
$ranks = Doggy_Config::$vars['app.point.init_user_ranks']['ranks'];
$init_rank_id = Doggy_Config::$vars['app.point.init_user_ranks']['init_rank_id'];
$point_type_code = Doggy_Config::$vars['app.point.init_user_ranks']['point_type_code'];
$last_rank_id = Doggy_Config::$vars['app.point.init_user_ranks']['point_type_code'];

$rank_model = new Sher_Core_Model_UserRankDefine();
$rank_seq_val = 1;
for ($i = 0; $i < count($ranks); $i++) {
    $rank = $ranks[$i];
    $data['_id'] = $rank_seq_val;
    $data['rank_id'] = $rank['rank_id'];
    $next_rank_id = $data['_id'] + 1;
    $data['next_rank_id'] = $data['_id'] + 1;
    $data['title'] = $rank['name'];
    $data['point_type'] = $point_type_code;
    $data['point_amount'] = $rank['point'];
    $rank_model->create($data);
    echo "rank:" . $rank_model->id ." created.\n";
    $rank_seq_val++;
}
$rank_model->set_rank_id_seq($rank_seq_val);

// init point events
echo "Prepare to init point events...\n";
$events = Doggy_Config::$vars['app.point.init_events'];
$event_model = new Sher_Core_Model_PointEvent();
$point_type = Doggy_Config::$vars['app.point.event_point_code'];
foreach ($events as $evt_row) {
    $data = array(
        '_id' => $evt_row['code'],
        'daily_limit' => $evt_row['daily_limit'],
        'point_type' => $point_type,
        'point' => $evt_row['point'],
        'name' => $evt_row['name'],
    );
    $event_model->create($data);
    echo "Evt:".$event_model->id." created\n";
}

?>