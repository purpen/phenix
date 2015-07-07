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

class Doggy_Dispatcher_MockAction extends Doggy_Dispatcher_Action_Lite {
    public function execute(){
        return Doggy_Dispatcher_Constant_Action::SUCCESS;
    }
}
class Doggy_Dispatcher_MockInterceptor extends Doggy_Dispatcher_Interceptor_Abstract   {
    public static $called=0;
    public function intercept(Doggy_Dispatcher_ActionInvocation $invocation){
        self::$called++;
        return $invocation->invoke();
    }
}
class Doggy_Dispatcher_MockResult implements Doggy_Dispatcher_Result {
    public  $called=0;
    function execute(Doggy_Dispatcher_ActionInvocation $invocation){
        $this->called++;
    }

}

class Doggy_Dispatcher_MockPreResultListener implements Doggy_Dispatcher_PreResultListener {          private $executed = false;
    function beforeResult(Doggy_Dispatcher_ActionInvocation $invocation,$code){
       // echo __CLASS__.'::'.__METHOD__." resultcode:$code \n";
        $this->setExecuted(true);
    }
    public function setExecuted($value){
        $this->executed = $value;
    }
    public function getExecuted(){
        return $this->executed;
    }
}

#init

Doggy_Config::set('app.dispatcher.interceptors.run',array(
    'Doggy_Dispatcher_MockInterceptor'
));
Doggy_Config::set('app.dispatcher.result.map',array('default'=>'Doggy_Dispatcher_MockResult'));

{
    $context = Doggy_Dispatcher_Context::getContext();
    Doggy_Dispatcher_MockInterceptor::$called=0;
    
    
    $invocation = new Doggy_Dispatcher_ActionInvocation($context,null,null);
    try{
        $invocation->getAction();
        fail('Expect exception for invalid action/method');
    }catch(Doggy_Exception $e){
    }
    
    $invocation = new Doggy_Dispatcher_ActionInvocation($context,'Doggy_Dispatcher_MockAction',null);
    is(get_class($invocation->getAction()),'Doggy_Dispatcher_MockAction','invoke/create action');
    
    $listener = new Doggy_Dispatcher_MockPreResultListener();
    $invocation->addPreResultListener($listener);
    $invocation->invoke();
    $result = $invocation->getResult();
    isnt($result,NULL,'invoke result');
    is($result->called,1,'invoke result called');
    is(Doggy_Dispatcher_MockInterceptor::$called,1,'invoke,interceptor called');
    ok($listener->getExecuted(),'invoke,pre_result listener');
}

{
    Doggy_Dispatcher_MockInterceptor::$called=0;
    $invocation = new Doggy_Dispatcher_ActionInvocation($context,'Doggy_Dispatcher_MockAction',null);
    $invocation->invokeActionOnly();
    is(Doggy_Dispatcher_MockInterceptor::$called,0,'invokeActionOnly');
}
?>