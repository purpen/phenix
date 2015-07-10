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

{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $request = new Doggy_Dispatcher_Request_Http();
    $request->setParam('foo','hello');
    is($request->getParam('foo'),'hello','set/get params');
}

{
    $_GET = array(
        'get' => true
    );
    $_POST = array(
        'post' => true
    );
    $params = array(
        'foo' => 'bar',
        'boo' => 'bah',
        'fee' => 'fi'
    );
    
    $request->setParams($params);
    $expected = $params + $_GET + $_POST;
    is($request->getParams(),$expected,'set_params merge GET/POST');
    
}
{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $_SERVER['PATH_INFO']='/test';
    $request = new Doggy_Dispatcher_Request_Http();
    is($request->getPathInfo(),'/test','get_path_info');
}
{
    $_GET=array();
    $_POST=array();
    $request = new Doggy_Dispatcher_Request_Http();
    $_SERVER['REQUEST_METHOD']='GET';
    is($request->getMethod(),'GET','get_method');
    
    $_SERVER['REQUEST_METHOD']='PUT';
    is($request->getMethod(),'PUT','get_method');
    ok($request->isPut(),'is_put');

    $_SERVER['REQUEST_METHOD']='DELETE';
    is($request->getMethod(),'DELETE','get_method');
    ok($request->isDelete(),'is_delete');
    
}

{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $_ENV['foo'] = 'env_foo';
    $request = new Doggy_Dispatcher_Request_Http();
    is($request->__get('foo'),'env_foo','__get');
    $_SERVER['foo'] = 'server_foo';
    is($request->__get('foo'),'server_foo','__get');
    $_COOKIE['foo'] = 'cookie_foo';
    is($request->__get('foo'),'cookie_foo','__get');
    $_POST['foo']='post_foo';
    is($request->__get('foo'),'post_foo','__get');
    $_GET['foo']='get_foo';
    is($request->__get('foo'),'get_foo','__get');
    $request->setParams(array('foo'=>'foo'));
    is($request->__get('foo'),'foo','__get');
}

{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $request = new Doggy_Dispatcher_Request_Http();
    $_ENV['foo'] = 'env_foo';
    ok(isset($request->foo),'__isset');
    $_COOKIE['ccc']=1;
    ok(isset($request->ccc),'__isset');
    ok(!isset($request->is_not_set_var),'__isset');
}

{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $_COOKIE= array();
    $_COOKIE['cookie_foo']='foo';
    $request = new Doggy_Dispatcher_Request_Http();
    is($request->getCookie('cookie_foo'),'foo','getCookie');
    is($request->getCookie('should_null'),NULL,'getCookie');
}

{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $_COOKIE= array();
    $_SERVER['test']='foo';
    $request = new Doggy_Dispatcher_Request_Http();
    is($request->getServer('test'),'foo','getServer');
    is($request->getServer('should_null'),NULL,'getServer');
}

{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $_COOKIE= array();
    $_POST['foo_test']='test';
    $request = new Doggy_Dispatcher_Request_Http();
    is($request->getPost('foo_test'),'test','getPost');
}

{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $_COOKIE= array();
    $_GET['foo_get']='test';
    $request = new Doggy_Dispatcher_Request_Http();
    is($request->getGet('foo_get'),'test','getGet');
}

{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $_COOKIE= array();
    
    $_SERVER['REQUEST_URI']     = '/index.php/news/3?var1=val1&var2=val2';
    $_SERVER['SCRIPT_NAME']     = '/home.php';
    $_SERVER['PHP_SELF']        = '/index.php/news/3';
    $_SERVER['SCRIPT_FILENAME'] = '/var/web/html/index.php';
    $_GET = array(
        'var1' => 'val1',
        'var2' => 'val2'
    );
    $request = new Doggy_Dispatcher_Request_Http();
    is($request->getBaseUrl(),'/index.php','get_base_url');
}

{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $_COOKIE= array();
    $url = '/foo.html';
    $_SERVER['REQUEST_URI']= $url;
    $request = new Doggy_Dispatcher_Request_Http();
    is($request->getRequestUri(),$url,'getRequestUri');
}

{
    $_SERVER['SERVER_PROTOCOL']='HTTP';
    $request = new Doggy_Dispatcher_Request_Http();
    ok(!$request->isSecure(),'isSecure');
    $_SERVER['HTTPS']='on';
    ok($request->isSecure(),'isSecure');
    
}
{
    $_SERVER['HTTP_X_REQUESTED_WITH']=1;
    $request = new Doggy_Dispatcher_Request_Http();
    ok($request->isAjaxRequest(),'isAjaxRequest');
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    $request->setIsAjaxRequest(null);
    ok(!$request->isAjaxRequest(),'isAjaxRequest');
    $_GET['HTTP_X_REQUESTED_WITH']=1;
    $request->setIsAjaxRequest(null);
    ok($request->isAjaxRequest(),'isAjaxRequest');
}
{
    $_SERVER['HTTP_X_REQUESTED_WITH']=1;
    $request = new Doggy_Dispatcher_Request_Http();
    ok($request->isAjaxRequest(),'setIsAjaxRequest');
    $request->setIsAjaxRequest(false);
    ok(!$request->isAjaxRequest(),'setIsAjaxRequest');
}
{
    $_SERVER['REMOTE_ADDR']='192.168.80.1';
    $request = new Doggy_Dispatcher_Request_Http();
    is($request->getClientIp(),'192.168.80.1');
}
{
    $_GET=array();
    $_POST=array();
    $_SERVER=array();
    $_SERVER['HTTP_ACCEPT']='text/xml';
    $_SERVER['HTTP_ACCEPT_ENCODING']='gbk';
    $_SERVER['HTTP_ACCEPT_LANGUAGE']='zh-cn';
    $_SERVER['HTTP_LAST_MODIFIED']='2007';
    $request = new Doggy_Dispatcher_Request_Http();
    is($request->getHeaders(),array(
        'Accept'=>'text/xml',
        'Accept-Encoding'=>'gbk',
        'Accept-Language'=>'zh-cn',
        'Last-Modified'=>'2007'
        ),'getHeaders');
}
{
    $_SERVER["SERVER_SOFTWARE"] = 'nginx';
    $request = new Doggy_Dispatcher_Request_Http();
    ok($request->isNginxServer(),'isNginxServer');
    ok(!$request->isLightyServer(),'isLightyServer');
}
?>