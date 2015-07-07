<?php
/**
 * Merge project config files and output as PHP array.
 * 
 */ 
 include_once "Doggy.php";
 
 if ($argc !=4 ) {
    echo "usage: php mk_runtime_config.php <config_dir> <custome_file_path> <output_merged_data_file>\n";
    exit(1);
 }
 
 $config_dir = $argv[1];
 $custom_config_file = $argv[2];
 $output_config_file   = $argv[3];
 
 // Doggy_Config::clear();
 echo "Load builtin config...";
 Doggy_Config::load_builtin_configs();
 echo "ok\n";
 echo "Load config files from $config_dir...";
 Doggy_Config::load_all_configs($config_dir);
 
 $meta_dir = $config_dir.'/../model_meta';
 
 echo "Load model meta files from $meta_dir ...";
 Doggy_Config::load_all_configs($meta_dir);
 
 echo "ok\n";
 echo "Merge runtime custom config file:".$custom_config_file."...";
 Doggy_Config::load_file($custom_config_file);
 echo "ok\n";
 echo "Flush merged config data into $output_config_file ...";
 
 if (Doggy_Config::dump_to($output_config_file)) {
     echo "ok.\n";
     exit(0);
 }
 else{
     echo "write failed.\n";
     exit(1);
 }
 exit(0);
?>