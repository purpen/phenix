<?php
/**
 * Edm邮件发送
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
echo "===============EDM WORKER WAKE UP===============\n";
echo "-------------------------------------------------\n";

echo "Start to send mail...\n";

$time = 10;

try{
    $task = new Sher_Core_Model_TaskQueue();
    $data = $task->pop();
    if(!empty($data)){
        // 获取邮件内容
        $edm_id = $data['task_data']['edm_id'];
        
        $edm = new Sher_Core_Model_Edm();
        $mail = $edm->extend_load((int)$edm_id);
        
        if(!empty($mail)){
            // 开始发送
            # Instantiate the client.
            $mgClient = new Mailgun('key-6k-1qi-1gvn4q8dpszcp8uvf-7lmbry0');
            $domain = "email.taihuoniao.com";

            $from = '太火鸟 <noreply@email.taihuoniao.com>';
            $subject = $mail['title'];
            $to = sprintf('%s <%s>', $data['task_data']['name'], $data['task_data']['email']);
            $html = $mail['mailbody'];
    
            # Make the call to the client.
            $result = $mgClient->sendMessage($domain, array(
                'from'    => $from,
                'to'      => $to,
                'subject' => $subject,
                'html'    => $html
            ));
            // 验证是否qq邮箱
            if(preg_match('/qq\.com/i', $data['task_data']['email'], $matches)){
                $time = 30;
            }
        }
    }
}catch(Sher_Core_Model_Exception $e){
    echo "Send mail failed: ".$e->getMessage();
}

echo "===========================EDM WORKER DONE==================\n";
echo "SLEEP TO NEXT LAUNCH .....\n";
// sleep 10/30 seconds
sleep($time);
exit(0);