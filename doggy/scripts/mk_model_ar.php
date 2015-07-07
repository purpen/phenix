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
$dba = Doggy_Dba_Manager::get_model_dba();
$fields = $dba->getFieldMetaList($table);
var_export($fields);
exit(0);
?>