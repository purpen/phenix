#!/usr/bin/env php
<?php
/**
 * fix user name => nickname
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

// 批量创建账号
function batch_create_user($start=1000){
	echo "Create batch the users ...\n";

    $user = new Sher_Core_Model_User();
    $data = array();
    $account_prefix = array(132,133,138,139,150,151,152,158,166,168,182,185,180,181,186,188,189,175,177,199);
    $cities = array('北京','深圳','广州','上海','杭州','南京','苏州','其他');
    $step = 1;
    $end = $start + 50000;
    for($i=$start; $i<=$end; $i+=$step){
        $account = (int)$account_prefix[array_rand($account_prefix, 1)].'00000000' + $i;
        $city = $cities[array_rand($cities, 1)];
        $email = (string)$account.'@139.com';
        $data = array(
            'account'  => (string)$account,
            'password' => sha1('thn456321'),
            'nickname' => (string)$account,
            'state'    => Sher_Core_Model_User::STATE_OK,
            'role_id'  => Sher_Core_Model_User::ROLE_USER,
            //'quality'  => 1,
            'sex'   => 1,
            'city'  => $city,
            'email' => $email,
            'kind'  => 9,
        );
        $profile = $user->get_profile();
        $profile['phone'] = (string)$account;
        $profile['job'] = '设计师';
        $data['profile'] = $profile;

        try{
            $ok = true;
            //$ok = $user->create($data);
            
            if($ok){
                echo "Create the user[$account] is ok!...num: $i...\n";
            }else{
                echo "Create the user[$account] is fail!...num: $i...\n";
            }
            
        }catch(Sher_Core_Model_Exception $e){
            echo "Create the user[$account] failed: ".$e->getMessage();
        }
        
        $step = rand(1,8);
        
        sleep($step);
    }
}

// 批量创建指定账号
function batch_create_special_user(){
	echo "Create batch the special users ...\n";

    $user_model = new Sher_Core_Model_User();
    $items = array();
    $account_arr = array(
        array('account'=>'18658155015', 'name'=>'白鸦'),
        array('account'=>'18602021777', 'name'=>'全曼午'),
        array('account'=>'18818886088', 'name'=>'胡晓'),

        array('account'=>'18618365588', 'name'=>'邹游'),
        array('account'=>'18600050877', 'name'=>'黎万强'),
        array('account'=>'17301088502', 'name'=>'高征'),
        array('account'=>'13903013061', 'name'=>'陈让'),
        array('account'=>'18910557654', 'name'=>'柯志雄'),
        array('account'=>'13501238916', 'name'=>'蔡虎'),

        array('account'=>'13910876313', 'name'=>'唐文'),
        array('account'=>'13817722326', 'name'=>'蔡丹枫'),
        array('account'=>'13605709179', 'name'=>'李琦'),
        array('account'=>'15911117798', 'name'=>'冯芳'),
        array('account'=>'18911630538', 'name'=>'刘楷'),
        array('account'=>'13925080706', 'name'=>'高翔'),

        array('account'=>'18810106222', 'name'=>'吴伟'),
        array('account'=>'13910891650', 'name'=>'sandy'),
        array('account'=>'13916354146', 'name'=>'贺欣浩'),
        array('account'=>'18621697102', 'name'=>'张文新'),
        array('account'=>'13911699177', 'name'=>'唐波'),
        array('account'=>'13902926645', 'name'=>'曾德钧'),

        array('account'=>'13601128410', 'name'=>'黄锐'),
        array('account'=>'15821350939', 'name'=>'薛晋琛'),
        array('account'=>'13764539974', 'name'=>'杨波'),
        array('account'=>'13501374302', 'name'=>'丛枫'),
        array('account'=>'13713775771', 'name'=>'李斌'),
        array('account'=>'18612539821', 'name'=>'黄夏琦'),

        array('account'=>'18958008521', 'name'=>'胡松'),
        array('account'=>'13911178022', 'name'=>'贾伟'),
        array('account'=>'15800040404', 'name'=>'胡传建'),
        array('account'=>'13811118430', 'name'=>'马力'),
        array('account'=>'13911198265', 'name'=>'许晓辉'),
        array('account'=>'13911670635', 'name'=>'吴声'),

        array('account'=>'18657193770', 'name'=>'刘宗孺'),
        array('account'=>'18006781005', 'name'=>'蚂蚁'),
        array('account'=>'18618488663', 'name'=>'朱君'),
        array('account'=>'13810079873', 'name'=>'任恬'),

    );

    for($i=0;$i<count($account_arr);$i++){
        $account = $account_arr[$i]['account'];
        $name = $account_arr[$i]['name'];
        $item = array();
        $user = $user_model->first(array('account'=>$account));
        if(!empty($user)){
            $item = array(
                'id' => $user['_id'],
                'account' => $user['account'],
                'name' => $name,
            );
            array_push($items, $item);
            echo "Exist the user[$account] is ok!...num: $i...\n";
        }else{
            $item = array(
                'account'  => (string)$account,
                'password' => sha1('123456'),
                'nickname' => (string)$account,
                'state'    => Sher_Core_Model_User::STATE_OK,
                'quality'  => 1,
            );
            $profile = $user_model->get_profile();
            $profile['phone'] = (string)$account;
            $profile['realname'] = $name;
            $item['profile'] = $profile;

            try{
                $ok = true;
                //$ok = $user_model->create($item);
                
                if($ok){
                    $user = $user_model->first(array('account'=>$account));
                    if(!empty($user)){
                        $item = array(
                            'id' => $user['_id'],
                            'account' => $user['account'],
                            'name' => $name,
                        );
                        array_push($items, $item);
                        echo "Create the user[$account] is ok!...num: $i...\n";                   
                    }else{
                        echo "Create the user[$account] is fail!...num: $i...\n";                   
                    }

                }else{
                    echo "Create the user[$account] is fail!...num: $i...\n";
                }
                
            }catch(Sher_Core_Model_Exception $e){
                echo "Create the user[$account] failed: ".$e->getMessage();
            }
            sleep(1);
        }

    } // endfor

    // 用户信息导出
    $fp = fopen('/home/tianxiaoyi/coffee_user.csv', 'a');
    // Windows下使用BOM来标记文本文件的编码方式 
    fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF));

    // 输出Excel列名信息
    $head = array('ID', '账号', '姓名');
    // 将数据通过fputcsv写到文件句柄
    fputcsv($fp, $head);

    for($i=0;$i<count($items);$i++){
        $row = array($items[$i]['id'], $items[$i]['account'], $items[$i]['name']);
        fputcsv($fp, $row);
    }

    fclose($fp);
    echo "total user info export over.\n";

}

$start = 20000;
//batch_create_user($start);
//batch_create_special_user();
