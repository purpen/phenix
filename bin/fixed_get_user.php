#!/usr/bin/env php
<?php
/**
 *fix user name => nickname
 */
error_reporting(0);
	//require_once dirname(__FILE__).'/bin/PHPExcel.php';
$drr = dirname(__FILE__).'/PHPExcel/Classes/PHPExcel.php';
	if (!file_exists($drr)){
		die("Can't find config_file: $drr\n");
}
include $drr;
//require_once dirname(__FILE__).'/PHPExcel/Classes/PHPExcel.php';


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

echo "Prepare to get user...\n";

/*try{
    $user = new Sher_Core_Model_User();
    $ok = $user->find();	
	$objPHPExcel=new PHPExcel();
	$objPHPExcel->getProperties()->setCreator('Sam.c')
	        ->setLastModifiedBy('Sam.c Test')
	        ->setTitle('Microsoft Office Excel Document')
	        ->setSubject('Office 2007 XLSX Document')
	        ->setDescription('Document for Office 2007 XLSX, generated using PHP classes.')
	        ->setKeywords('office 2007 openxml php')
	        ->setCategory('Result file');
	$objPHPExcel->setActiveSheetIndex(0)
	            ->setCellValue('A1','ID')
	            ->setCellValue('B1','会员名')
	            ->setCellValue('C1','密码')
	            ->setCellValue('D1','支付宝账号');
	
	
	$i=2; 
	foreach($ok as $k=>$v){
	 $objPHPExcel->setActiveSheetIndex(0)
	            ->setCellValue('A'.$i,$v['_id'])
	            ->setCellValue('B'.$i,$v['nickname'])
	            ->setCellValue('C'.$i,$v['password'])
	            ->setCellValue('D'.$i,$v['account']);
	 $i++;
	}
	$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(35);
	$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(35);
	$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(35);
	$objPHPExcel->getDefaultStyle()->getFont()->setSize(14);

	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
	header('Cache-Control: max-age=0');
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

	$objWriter->save('1.xls');
	exit;

    }catch(Sher_Core_Model_Exception $e){
	echo "Get the user failed: ".$e->getMessage();
		
	continue;

   }*/
try{
    $user = new Sher_Core_Model_User();
    $ok = $user->find();
	header('Content-Type: application/vnd.ms-excel'); 
	header('Content-Disposition: attachment;filename="user.csv"'); 
	header('Cache-Control: max-age=0');
	$fp = fopen('php://output','a');
	$head = array('姓名', '性别', '年龄', 'Email', '电话', '……'); 
	foreach ($head as $i => $v) { 
	// CSV的Excel支持GBK编码，一定要转换，否则乱码 
	$head[$i] = iconv('utf-8', 'gbk', $v); 
	}
	fputcsv($fp, $head);
	$cnt = 0; 
	// 每隔$limit行，刷新一下输出buffer，不要太大，也不要太小 
	$limit = 100000;
	// 逐行取出数据，不浪费内存 
	while ($row = $ok) {
	$cnt ++; 
	if ($limit == $cnt) { //刷新一下输出buffer，防止由于数据过多造成问题 
	ob_flush(); 
	flush(); 
	$cnt = 0; 
	}
	foreach ($row as $i => $v) { 
	$row[$i] = iconv('utf-8', 'gbk', $v); 
	} 
	fputcsv($fp, $row); 
	}



	}catch(Sher_Core_Model_Exception $e){
	echo "Get the user failed: ".$e->getMessage();
		
	continue;

   }

?>


