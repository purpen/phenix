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

Doggy_Config::set('app.auth.provider.default',array(
    'class'=>'Doggy_Dispatcher_Interceptor_AuthTest_MockAuthProvider',
    ));
Doggy_Config::set('app.dispatcher.interceptors.auth',array('authen_action'=>'Doggy_Dispatcher_Interceptor_AuthTest_MockAuthenAction'));


require dirname(__FILE__).'/auth_test.php';

$itr = new Doggy_Dispatcher_Interceptor_Auth();

$itr->init();



$authen = Doggy_Auth_Authentication_Manager::getProvider()->createAuthentication();


$_SERVER['REQUEST_URI'] = '/test';

$server = Doggy_Mock_Server::mock('Doggy_Dispatcher_Interceptor_AuthTest_AuthResource','any');


//1.访问的方法没有出现在privilege 列表,应可以正常访问
{
    
    $server->invokeInterceptor($itr);
    $action = $server->getAction();

    ok($action instanceof Doggy_Dispatcher_Interceptor_AuthTest_AuthResource,'auth interceptor:unlimited action');
    is($server->getActionMethod(),'any','auth interceptor:unlimited action');
}
//2.访问的方法必须登录
//未登录
{
    $server->reset();
    $server->resetAction('Doggy_Dispatcher_Interceptor_AuthTest_AuthResource','any_login');
    $server->invokeInterceptor($itr);
    $action = $server->getAction();
    ok($action instanceof Doggy_Dispatcher_Interceptor_AuthTest_MockAuthenAction,'allow any login(before)');
    is($server->getActionMethod(),'login','allow any login(before)');
}

{
    //登录后
    $authen->setAuthenticated(true);
    $server->reset();
    $server->resetAction('Doggy_Dispatcher_Interceptor_AuthTest_AuthResource','any_login');
    $server->invokeInterceptor($itr);
    $action = $server->getAction();
    ok($action instanceof Doggy_Dispatcher_Interceptor_AuthTest_AuthResource,'allow any login(after)');    
}

{
    //3.权限不足
    $server->reset();
    $server->resetAction('Doggy_Dispatcher_Interceptor_AuthTest_AuthResource','delete');
    $server->invokeInterceptor($itr);
    $action = $server->getAction();
    ok($action instanceof Doggy_Dispatcher_Interceptor_AuthTest_MockAuthenAction,'no access');
    is($server->getActionMethod(),'deny','no access');
}

{
    //4.权限通过
    $server->reset();
    $server->resetAction('Doggy_Dispatcher_Interceptor_AuthTest_AuthResource','edit');
    $server->invokeInterceptor($itr);
    $action = $server->getAction();
    ok($action instanceof Doggy_Dispatcher_Interceptor_AuthTest_AuthResource,'access passed');    
}

{
    //5.自定义权限检查
    $server->reset();
    $server->resetAction('Doggy_Dispatcher_Interceptor_AuthTest_AuthResource','custom');
    $action = $server->getAction();
    $action->setCustomResult(true);
    $server->invokeInterceptor($itr);
    $action = $server->getAction();
    ok($action instanceof Doggy_Dispatcher_Interceptor_AuthTest_AuthResource,'custom access check');    
    $server->reset();
    $server->resetAction('Doggy_Dispatcher_Interceptor_AuthTest_AuthResource','custom');
    $action = $server->getAction();
    $action->setCustomResult(false);
    $server->invokeInterceptor($itr);
    $action = $server->getAction();
    ok($action instanceof Doggy_Dispatcher_Interceptor_AuthTest_MockAuthenAction,'custom access check');
    is($server->getActionMethod(),'deny','custom access check');
    
}
?>