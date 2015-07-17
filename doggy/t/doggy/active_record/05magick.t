#!/usr/bin/env php
<?php
if (getenv('DOGGY_TEST_CLASS_PATH')) {
    set_include_path(getenv('DOGGY_TEST_CLASS_PATH'));
}
require "Test.php";
require "Doggy.php";

if (getenv('DOGGY_APP_ROOT')) {
    $doggy_app_root = getenv('DOGGY_APP_ROOT');
    define('DOGGY_APP_ROOT',$doggy_app_root);
    require $doggy_app_root.'/var/test.rc';
}
/*
Test howto:
-----------------------------------------------------------
plan($num); # plan $num tests
# or
plan('no_plan'); # We don't know how many
# or
plan('skip_all'); # Skip all tests
# or
plan('skip_all', $reason); # Skip all tests with a reason

diag('message in test output') # Trailing \\n not required
# $test_name is always optional and should be a short description of
# the test, e.g. "some_function() returns an integer"

# Various ways to say "ok"
ok($have == $want, $test_name);

# Compare with == and !=
is($have, $want, $test_name);
isnt($have, $want, $test_name);

# Run a preg regex match on some data
like($have, $regex, $test_name);
unlike($have, $regex, $test_name);

# Compare something with a given comparison operator
cmp_ok($have, '==', $want, $test_name);
# Compare something with a comparison function (should return bool)
cmp_ok($have, $func, $want, $test_name);

# Recursively check datastructures for equalness
is_deeply($have, $want, $test_name);

# Always pass or fail a test under an optional name
pass($test_name);
fail($test_name);

# TODO tests, these are want to fail but won't fail the test run,
# unwant success will be reported
todo_start("integer arithmetic still working");
ok(1 + 2 == 3);
{
    # TODOs can be nested
    todo_start("string comparison still working")
    is("foo", "bar");
    todo_end();
}
todo_end();
*/
//Now, let's rock!

include dirname(__FILE__).'/common_inc.php';

$dba = Doggy_Dba_Manager::get_model_dba();

setup_test_tables();

Doggy_ActiveRecord_Base::setDba($dba);
Doggy_ActiveRecord_Base::disableInternalCache();

$model = new Doggy_ActiveRecord_BaseTest_Me();


#afterFind event
{
    $test_name ='afterFind event trigger';
    ok(!$model->getAfterFindTrigger(),'afterFind check');
    
    $result= $model->find(array('condition'=>'id=1'));
    
    ok($model->getAfterFindTrigger(),'afterFind trigger!');
    
    
    //some magic usage ;-)
    
    is($model[0]['name'],'A1',"ArrayAccess");
    
    //foreach, use Iterator
    foreach($model as $row){
        $name = $row['name'];
        is($row['name'],'A1','iterator (foreach)');
    }

}

{
	$dba->execute('TRUNCATE TABLE me');
	$dba->execute('TRUNCATE TABLE women');
	$dba->execute('TRUNCATE TABLE child');
	
	$model->insert();
    $model->setName('A1');
    $model->addRelationModelData('children',array('name'=>'C10'));
    $model->addRelationModelData('children',array('name'=>'C11'));
    $model->save();
    $id = $model->getId();
    $model->insert();
    $model->setName('A2');
    $model->addRelationModelData('children',array('name'=>'C20'));
    $model->addRelationModelData('children',array('name'=>'C21'));
    $model->save();
    $id2 = $model->getId();
    
    $model->find(array('order'=>'id ASC'));
    $data = serialize($model);
    $model2 = unserialize($data);
    //var_dump($model2);
    
    $child1 = $model2[0]['children'];
    is(2,$child1->count());
    is("C10",$child1[0]['name'],'relation field');
    is("C11",$child1[1]['name'],'relation field');
    $child2 = $model2[1]['children'];
    is(2,$child2->count());
    is("C20",$child2[0]['name'],'relation field');
    is("C21",$child2[1]['name'],'relation field');
    
    //test magic field
    is("A1M1",$model2[0]['m1'],'magic field');
    is("A1M2",$model2[0]['m2'],'magic field');
    is("A2M1",$model2[1]['m1'],'magic field');
    is("A2M2",$model2[1]['m2'],'magic field');
}

{
	$dba->execute('TRUNCATE TABLE me');
	$dba->execute('TRUNCATE TABLE women');
	$dba->execute('TRUNCATE TABLE child');
	
    $dba->execute('INSERT INTO me (id,name,women_id)VALUES(?,?,?)',array(1,'A',2));
    $dba->execute('INSERT INTO women (id,name,husband)VALUES(?,?,?)',array(2,'AW_wife',1));
    $dba->execute('INSERT INTO child (id,me_id,women_id,name)VALUES(?,?,?,?)',array(3,1,2,"AW_child"));
    $result = $model->findById(1)->getExtendResultArray(array(
        "m1"=>array(),
        "m2"=>array(),
        "wife"=>array("children"=>array("father"=>array("wife"=>array())))
    ));
    is("AM1",$result["m1"],'getExtendResultArray');
    is("AM2",$result["m2"],'getExtendResultArray');
    is("AW_wife",$result["wife"]["name"],'getExtendResultArray');
    is("AW_child",$result["wife"]["children"][0]["name"],'getExtendResultArray,deeply 2');
    is("A",$result["wife"]["children"][0]["father"]["name"],'getExtendResultArray,deeply 2');
    is("AW_wife",$result["wife"]["children"][0]["father"]["wife"]["name"],'getExtendResultArray,deeply 3');
    
}
{
	unset($result);
	$data=array('name'=>'a','age'=>5);
    $model = new Doggy_ActiveRecord_BaseTest_Me();
    $row = new Doggy_ActiveRecord_Base_ResultRow($data,$model,array());
    
    is($row->keys(),array('name','age'),'ResutRow,keys');
    is($row['name'],'a','ResutRow,value');
    $row['name']='b';
    is($row['name'],'b','ResutRow,value');
    
    foreach($row as $k=>$v){
        is($data[$k],$v,'ResutRow,foreach');
    }
}

{
	$model = new Doggy_ActiveRecord_BaseTest_Me();
	
	unset($result);
	
    $result[] = array('name'=>'A1');
    $result[] = array('name'=>'A2');
    $result[] = array('name'=>'A3');

    $rs = new Doggy_ActiveRecord_Base_ResultSet($result,$model,array());
    
    is(3,count($rs),'ResultSet, count');
	
    for($i=0;$i<count($rs);$i++){
        is("A".($i+1),$rs[$i]['name'],'ResultSet,value');
    }
    //注意，和普通的foreach不同,rs的foreach的value是引用形式的,而不是copy
    //因此,你可以直接修改内容
    foreach($rs as $k=>$row){
        //echo "index:$k => ".$row['name']."\n";
        $row['name']='B';
    }
    foreach($rs as $k=>$row){
        is($row['name'],"B",'ResultSet,modify');
    }
    
}

clean_test_tables();
?>