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


#one-many
{

    $model->setValidateOk(true);
    /**
     * HasMany 类型 （1-*)
     */
    $model->insert();
    $model->setName('A');
    $model->addRelationModelData('children',array('name'=>'C1'));
    $model->addRelationModelData('children',array('name'=>'C2'));
    $model->save();
    
    $id = $model->getId();
    
    
    $me = new Doggy_ActiveRecord_BaseTest_Me();
    //设置外键值
    $me->setId($id);
    
    $children = $me->findRelationModel('children');
    
    is($children->count(),2,'findRelation,1-*,count');
    ok($children instanceof Doggy_ActiveRecord_BaseTest_Child,'findRelation,1-*,model_class');
    
    //测试覆盖预定义的options
    //只返回1条记录,提供外键值
    $me = new Doggy_ActiveRecord_BaseTest_Me();
    $children = $me->findRelationModel('children',array('size'=>1),$id);
    is($children->count(),1,'findRelation,1-*,override options(size)');
    //附加的vars
    $children = $model->findRelationModel('children',array('condition'=>'name=?','vars'=>array('C1')),$id);
    is($children->count(),1,'findRelation,1-*,override options(condition)');
    is($children[0]['name'],"C1",'findRelation,1-*,override options(condition)');
}
#has-one
{
    /**
     * HasOne 类型
     */
    //prepare test data
    $model->insert();
    $model->setName('B');
    $model->addRelationModelData('wife',array('name'=>'W'));
    $model->save();
    $id = $model->getId();
    
    $me = new Doggy_ActiveRecord_BaseTest_Me();
    //设置默认的外键值
    $me->setId($id);
    $wife = $me->findRelationModel('wife');
    is($wife['name'],'W','findRelation,has_one,fetch');
    //直接提供外键值,可以覆盖刚才的默认外键值
    $wife = $me->findRelationModel('wife',array(),5);
    is($wife->count(),0,'findRelation,has_one,override foreigin key');
}

#belongs_to
{    
    //prepare test data
    $child = new Doggy_ActiveRecord_BaseTest_Child();
    $child->setName('boy');
    $child->addRelationModelData('father',array('name'=>'boy_father'));
    $child->addRelationModelData('mother',array('name'=>'boy_mother'));
    $child->save();
    $id = $child->getId();
    $father_id = $child->getMeId();
    $mother_id = $child->getWomenId();
    unset($child);
    
    $child = new Doggy_ActiveRecord_BaseTest_Child();
    $child->disableInternalCache();
    
    $mother = $child->findRelationModel('mother',array(),$mother_id);
    
    $child->setMeId($father_id);
    $father = $child->findRelationModel('father');
    
    is($mother['name'],'boy_mother','findRelation,belongs_to');
}
#many_to_many
{
    $people = new Doggy_ActiveRecord_BaseTest_People();
    $people->insert();
    $people->setName('P1');
    $people->save();
    $p1 = $people->getId();
    
    $people->insert();
    $people->setName('P2');
    $people->save();
    $p2 = $people->getId();
    
    
    $people->insert();
    $people->setName('P3');
    $people->save();
    $p3 = $people->getId();
    
    
    $model->insert();
    $model->setName('AA');
    $model->addRelationModelData('friends',array('people_id'=>$p1,'type'=>1));
    $model->addRelationModelData('friends',array('people_id'=>$p2,'type'=>1));
    $model->save();
    $id = $model->getId();
    //开始测试
    $me = new Doggy_ActiveRecord_BaseTest_Me();
    //设置默认的外键值
    $me->setId($id);
    $friends = $me->findRelationModel('friends');
    is($friends->count(),2,'findRelation,*-*,fetch');
    
    $friends = $me->findRelationModel('friends',array('condition'=>'name=?','vars'=>array('P1')));
    
    is($friends->count(),1,'findRelation,*-*,fetch,ovrride (condition)');
    is($friends[0]['name'],'P1','findRelation,*-*,fetch,ovrride (condition)');
    is($friends[0]['type'],1,'findRelation,*-*,fetch,ovrride (condition)');
    
    //测试相同id是否冲突，type是否起作用
    $child = new Doggy_ActiveRecord_BaseTest_Child();
    //设置和刚才的me的id一样
    $child->setId($id);
    $child->setName('C');
    $child->addRelationModelData('friends',array('people_id'=>$p3,'type'=>4));
    $child->save();
    
    $id = $child->getId();
    
    $friends = $child->findRelationModel('friends');
    is($friends->count(),1,'addRelationModelData,*-*');
    
}

# test relation save
{
 
   $model->setValidateOk(true);
    /**
     * HasMany 类型 （1-*)
     */
    $model->insert();
    $model->setName('A');
    $model->addRelationModelData('children',array('name'=>'C1'));
    $model->addRelationModelData('children',array('name'=>'C2'));
    $model->save();
    $id = $model->getId();
    $child = new Doggy_ActiveRecord_BaseTest_Child();
	is($child->countIf('me_id=?',array($id)),2,'save relastion, 1-*');
    
    //update mode
    $child2 = new Doggy_ActiveRecord_BaseTest_Child();
    $child2->setName('C3');
    $model->addRelationModel('children',$child2);
    $model->save();
    
	is($child->countIf('me_id=?',array($id)),3,'save relastion(update), 1-*');
	is($child->countIf('me_id=? AND name=?',array($id,'C3')),1,'save relastion(update), 1-*');
	
	//test destroy relation
    $model->destroy();
	ok(!$child->hasIf('me_id=?',array($id)),'destroy relation,1-*');
    
}

{    
    // HasOne 类型（1-1)
    //----------------
    $model->insert();
    $model->setName('A');
    $model->addRelationModelData('wife',array('name'=>'W'));
    $model->save();
    $id = $model->getId();
    
    $women = new Doggy_ActiveRecord_BaseTest_Women();
    is($women->countIf('husband=?',array($id)),1,'save relation,1-1');

    //update mode
    $model->insert();
    $model->setName('A');
    $women->setName('W2');
    $model->addRelationModel('wife',$women);
    $model->save();
    $id = $model->getId();
    is($women->countIf('husband=? AND name=?',array($id,'W2')),1,'save relation(update),1-1');
}

{
    //从属类型测试 （*-1)
    ////////////////////////
    
    $child = new Doggy_ActiveRecord_BaseTest_Child();
    $child->setName('boy');
    $child->addRelationModelData('father',array('name'=>'boy_father'));
    $child->addRelationModelData('mother',array('name'=>'boy_mother'));
    $child->save();
    
    $id = $child->getId();
    $father_id = $child->getMeId();
    $mother_id = $child->getWomenId();
    
    is($model->countIf('id=? AND name=?',array($father_id,'boy_father')),1,'save relation,belongs_to');
    is($women->countIf('id=? AND name=?',array($mother_id,'boy_mother')),1,'save relation,belongs_to');
    
    //belongsto 不应删除关联的对象
    $child->destroy();
    is($model->countIf('id=? AND name=?',array($father_id,'boy_father')),1,'destroy relation,belongs_to');
    is($women->countIf('id=? AND name=?',array($mother_id,'boy_mother')),1,'save relation,belongs_to');
}

{
    //关联表类型（*-*)
    $model->insert();
    $model->setName('AA');
    $model->addRelationModelData('friends',array('people_id'=>1,'type'=>1));
    $model->addRelationModelData('friends',array('people_id'=>2,'type'=>1));
    $model->save();
    $id = $model->getId();
    
    $sql='SELECT COUNT(*) AS cnt FROM my_friend WHERE my_id=? AND type=?';
    $row = $dba->query($sql,1,1,array($id,1));
    
    is($row[0]['cnt'],2,'save relation,*-*');
    
    //反复添加，应该只保存最后一次的
    $model->addRelationModelData('friends',array('people_id'=>1,'type'=>1));
    $model->save();
    
    $row = $dba->query($sql,1,1,array($id,1));
    is($row[0]['cnt'],1,'save relation,*-*,no more rows saved');
    
    //应该自动删除连接表的记录
    $model->destroy();
    $row = $dba->query($sql,1,1,array($id,1));
    is($row[0]['cnt'],0,'destroy relation,*-*');

}

{
	clean_test_tables();
	setup_test_tables(false);
	
	$model = new Doggy_ActiveRecord_BaseTest_Me();
	
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
    
    $child1 = $model[0]['children'];
    
    is(2,$child1->count());
    is("C10",$child1[0]['name'],'lazy load relation');
    is("C11",$child1[1]['name'],'lazy load relation');
    $child2 = $model[1]['children'];
    is(2,$child2->count());
    is("C20",$child2[0]['name'],'lazy load relation');
    is("C21",$child2[1]['name'],'lazy load relation');

}

clean_test_tables();

?>