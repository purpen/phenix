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

setup_test_tables(false);

Doggy_ActiveRecord_Base::setDba($dba);
Doggy_ActiveRecord_Base::disableInternalCache();


{
    $test_name = 'setter/getter';
	$model = new Doggy_ActiveRecord_BaseTest_Me();
    is($model->getTableName(),'me',"$test_name tablename");
    is($model->getSequenceName(),'me',"$test_name sequence_name");
    is($model->getPrimaryKey(),'id',"$test_name primary_key");
    is($model->setPrimaryKey('b')->getPrimaryKey(),'b',"$test_name primary_key");
    is($model->getClassName(),'Doggy_ActiveRecord_BaseTest_Me',"$test_name class_name");
    is($model->setRawData(array())->getRawData(),array(),"$test_name raw_data");
    
}
#getModel
{
	$model = Doggy_ActiveRecord_BaseTest_Me::getModel();
    ok($model instanceof Doggy_ActiveRecord_BaseTest_Me,'getModel');
    $model = Doggy_ActiveRecord_BaseTest_Me::getModel(array('name'=>'P'));
    is($model->getName(),"P",'getModel pass args');
}
#tablelize
{
	$model = new Doggy_ActiveRecord_BaseTest_Me();
	is($model->tablelize(),'me','tablelize');
    is($model->tablelize('bb'),'bb','tablelize');
    is($model->setTableNamePrefix('t_')->setTableNameSuffix('_f')->tablelize(),'t_me_f', 'tablelize,prefix,surfix');
    is($model->tablelize('bb'),'t_bb_f','tablelize,prefix,surfix');
}

# getDba
{
	isnt(Doggy_ActiveRecord_BaseTest_Me::getDba(),NULL,'getDba');
    ok(Doggy_ActiveRecord_BaseTest_Me::getDba() instanceof Doggy_Dba_Adapter,'getDBA');
}

{
	$model = new Doggy_ActiveRecord_BaseTest_Me();
    is(get_class($model),$model->getClassName(),'getClassName');
}

{
	$model = new Doggy_ActiveRecord_BaseTest_Me();
    is($model->getJoinTableName('a','b'),'a_b','getJoinTableName');
    is($model->getJoinTableName('b2','a1'),'a1_b2','getJoinTableName');
    is($model->getJoinTableName('52_a','10_b'),'10_b_52_a','getJoinTableName');
}

{
	$dba->execute('TRUNCATE TABLE me');
	
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(1,'A'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(9,'B'));

    $model = new Doggy_ActiveRecord_BaseTest_Me();

    is(1,$model->countIf('id=1'),'countIf');
    is(2,$model->countIf('id<?',array(10)),'countIf,condition');
    is(1,$model->countIf('name=?',array('B')),'countIf,condition');
    is(0,$model->countIf('name=? AND id=?',array('B',8)),'countIf,condition');
	$dba->execute('TRUNCATE TABLE me');
}

{
	$dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(1,'A'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(9,'A'));
	$model = new Doggy_ActiveRecord_BaseTest_Me();
    ok($model->has(1),'has');
    ok($model->has(9),'has');
    ok(!$model->has(-999),'has');
    ok(!$model->has(999),'has');
    $dba->execute('TRUNCATE TABLE me');
}

{
	$dba->execute('TRUNCATE TABLE me');
	$dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(1,'A'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(9,'B'));
    $model = new Doggy_ActiveRecord_BaseTest_Me();
    ok($model->hasIf('id<2 AND id>0'),'hasIf');
    ok($model->hasIf('name=? AND id=?',array('A',1)),'hasIf');
    $dba->execute('TRUNCATE TABLE me');
}

{
	$i=1;
	is($model->genId(),$i++,'genId .. 1');
	is($model->genId(),$i++,'genId .. 2');
	is($model->genId(),$i++,'genId .. 3');
}



clean_test_tables();
?>