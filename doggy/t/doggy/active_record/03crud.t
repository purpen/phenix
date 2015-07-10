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
$model = new Doggy_ActiveRecord_BaseTest_Me();

#insert
{
	$model->setIsNew(false);
    $model->setName('A');
    $model->insert();
	ok($model->isNew(),'insert,is_new');
	is($model->getRawData(),array(),'insert,raw_data');
}

#save
{
	//basic test
    $model->setName('P');
    $model->save();
    
    //check event invoke
    //should never invoke these events
    is($model->getEventTicks('beforeDestory'),NULL,'save,no tbeforeDestory');
    is($model->getEventTicks('afterDestory'),NULL,'save,no afterDestory');
    is($model->getEventTicks('beforeUpdate'),NULL,'save,no beforeUpdate');
    is($model->getEventTicks('afterUpdate'),NULL,'save,no afterUpdate');

    //this is a new record,should raise these events one by one
    $i=0;
	is($model->getEventTicks('beforeValidation'),$i++,"save, event $i => beforeValidation");
	is($model->getEventTicks('validate'),$i++,"save, event $i => validate");
	is($model->getEventTicks('afterValidation'),$i++,"save, event $i => afterValidation");
	is($model->getEventTicks('beforeSave'),$i++,"save, event $i => beforeSave");
	is($model->getEventTicks('beforeCreate'),$i++,"save, event $i => beforeCreate");
	is($model->getEventTicks('afterCreate'),$i++,"save, event $i => afterCreate");
	is($model->getEventTicks('afterSave'),$i++,"save, event $i => afterSave");
	
	is($model->getId(),1,'save,check id');
	
    $result = $model->findById(1);
	is($result['name'],'P','save,check name');

    //let's do again,but now should be update-mode
    $model->resetEventMarks();
    $model->setId(1);
    $model->setName('P2');
    $model->save();
    //should never invoke these events
	is($model->getEventTicks('beforeDestory'),NULL,'save(update),no beforeDestory');
    is($model->getEventTicks('afterDestory'),NULL,'save(update),no afterDestory');
    is($model->getEventTicks('beforeCreate'),NULL,'save(update),no beforeUpdate');
    is($model->getEventTicks('afterCreate'),NULL,'save(update),no afterUpdate');


    $i=0;
	is($model->getEventTicks('beforeValidation'),$i++,"save(update), event $i => beforeValidation");
	is($model->getEventTicks('validate'),$i++,"save(update), event $i => validate");
	is($model->getEventTicks('afterValidation'),$i++,"save(update), event $i => afterValidation");
	is($model->getEventTicks('beforeSave'),$i++,"save(update), event $i => beforeSave");
	is($model->getEventTicks('beforeUpdate'),$i++,"save(update), event $i => beforeUpdate");
	is($model->getEventTicks('afterUpdate'),$i++,"save(update), event $i => afterUpdate");
	is($model->getEventTicks('afterSave'),$i++,"save(update), event $i => afterSave");

	is($model->getId(),1,'save(update),check id');
	
    $result = $model->findById(1);
	is($result['name'],'P2','save(update),check name');


    //test validate error
    $model->insert();
    $model->setValidateOk(false);
    try{
        $model->save();
    }catch(Doggy_ActiveRecord_ValidateException $e){
		is($e->getMessage(),'validate error','validate error');
		is($e->getDetailErrors(),array('validate error'),'validate error');
    }
}

#destory
{
	$dba->execute('TRUNCATE TABLE me');
	$model = new Doggy_ActiveRecord_BaseTest_Me();
	
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(1,'A1'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(9,'A2'));
    
    
    $model->setId(1);
    $model->destroy();

    //check event invoke
    //should never invoke these events
    is($model->getEventTicks('beforeUpdate'),NULL,'destory,no beforeUpdate');
    is($model->getEventTicks('afterUpdate'),NULL,'destory,no afterUpdate');
    is($model->getEventTicks('beforeValidation'),NULL,'destory,no beforeValidation');
    is($model->getEventTicks('validate'),NULL,'destory,no validate');
    is($model->getEventTicks('afterValidation'),NULL,'destory,no afterValidation');
    is($model->getEventTicks('beforeSave'),NULL,'destory,no beforeSave');
    is($model->getEventTicks('beforeCreate'),NULL,'destory,no beforeCreate');
    is($model->getEventTicks('afterCreate'),NULL,'destory,no afterCreate');
    is($model->getEventTicks('afterSave'),NULL,'destory,no afterSave');

    //should raise these events one by one
    $i=0;
    is($i++,$model->getEventTicks('beforeDestroy'),'destory,event $i => beforeDestory');
    is($i++,$model->getEventTicks('afterDestroy'),'destory,event $i => afterDestory');

    ok(!$model->has(1),'detroy,check persistent');
    $model->destroy(9);
    ok(!$model->has(9),'detroy,check persistent');
}

#destroy_all
{
	$dba->execute('TRUNCATE TABLE me');
	$dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(1,'A1'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(9,'A2'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(10,'B'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(11,'B'));
    $model = new Doggy_ActiveRecord_BaseTest_Me();
    $model->destroyAll("name=?",array('B'));
    ok(!$model->hasIf('name=?',array('B')),'destroyAll');
	is($model->countIf(),2,'destroyAll');
}

#delete
{
	$dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(1,'A'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(9,'B'));
    $model->delete(1);
    ok(!$model->has(1),'delete');
    $model->delete(9);
    ok(!$model->has(9),'delete');
}

#delete_all
{
	$dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(1,'A'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(2,'A'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(9,'B'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(11,'B'));
    $dba->execute('INSERT INTO me (id,name)VALUES(?,?)',array(12,'B'));
    
    $model->deleteAll("name=?",array('B'));
    ok(!$model->hasIf('name=?',array('B')),'delete_all');
    $model->deleteAll('id<3');
    ok(!$model->hasIf('id<3'),'delete_all');
}

#validateRequird
{
    ok(!$model->validateRequird(array('id')),'validateRequird ..id');
    $model->setId(1);
	ok($model->validateRequird(array('id')),'validateRequird ..id');
	ok(!$model->validateRequird(array('name')),'validateRequird ..name');
    $model->setName('a');
	ok($model->validateRequird(array('name','id')),'validateRequird');
    ok(!$model->validateRequird(array('name','id','unknown')),'validateRequird');
}



clean_test_tables();
?>