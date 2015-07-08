<?php
include_once "Doggy.php";
if (getenv('DOGGY_APP_ROOT')) {
    $doggy_app_root = getenv('DOGGY_APP_ROOT');
    define('DOGGY_APP_ROOT',$doggy_app_root);
    require $doggy_app_root.'/var/doggy_app.rc';
}

$table = $argv[1];
if ($argc !=2 ) {
    exit(1);
}
$db = Doggy_Db_Manager::get_db();
$fields = $db->get_fields($table);

require_once 'spyc.php';
$yml = new Spyc();
echo $yml->dump(array('app.model.meta.'.$table => $fields));
exit(0);
?>