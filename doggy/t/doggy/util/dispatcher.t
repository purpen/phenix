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
class Doggy_Util_DispatcherTest_T {
    public $params=array();
    public function setA($v){
        $this->params['a']=$v;
    }
    public function setB($v){
        $this->params['b']=$v;
    }
    public function setC($v){
        $this->params['c']=$v;
    }
}
class Doggy_Util_DispatcherTest_T2 extends Doggy_Util_DispatcherTest_T{
    public function acceptableName($name){
        if($name=='c')return false;
        return true;
    }
}

{
    $params = array('a'=>1,'b'=>2,'c'=>3);
    $obj = new Doggy_Util_DispatcherTest_T();
    Doggy_Util_Dispatcher::applyParams($obj,$params);
    is($obj->params['a'],"1",'applyParams');
    is($obj->params['b'],"2",'applyParams');
    is($obj->params['c'],"3",'applyParams');
    $obj2 = new Doggy_Util_DispatcherTest_T2();
    Doggy_Util_Dispatcher::applyParams($obj2,$params);
    ok(!isset($obj2->params['c']),'applyParams');
    
}

{
    $invocation = Doggy_Mock_Server::createMockAcitionInvocation();
    
    $request = $invocation->getInvocationContext()->getRequest();
    
    $request->setParam('a',1);
    $request->setParam('b',2);
    $request->setParam('c',3);
    
    $obj = new Doggy_Util_DispatcherTest_T();
    $obj2 = new Doggy_Util_DispatcherTest_T2();
    
    Doggy_Util_Dispatcher::applyDispatcherContextParams($invocation->getInvocationContext(),$obj);
    Doggy_Util_Dispatcher::applyDispatcherContextParams($invocation->getInvocationContext(),$obj2);
    is($obj->params['a'],"1",'applyDispatcherContextParams');
    is($obj->params['b'],'2','applyDispatcherContextParams');
    is($obj->params['c'],"3",'applyDispatcherContextParams');
    ok(!isset($obj2->params['c']),'applyDispatcherContextParams');
    
}


?>