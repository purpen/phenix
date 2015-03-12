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
    $response = new Doggy_Dispatcher_Response_Http();
    $response->setCharacterEncoding('utf-8');
    is($response->getCharacterEncoding(),'utf-8','set/getCharacterEncoding');
}

{
    $response = new Doggy_Dispatcher_Response_Http();
    $response->setCharacterEncoding(null);
    $response->setContentType('text/xml');
    is($response->getContentType(),'text/xml','getContentType');
    $response->setCharacterEncoding('utf-8');
    is($response->getContentType(),'text/xml; charset=utf-8','getContentType');
}

{
    $response = new Doggy_Dispatcher_Response_Http();
    $time = time();
    $response->setLastModified($time);
    is($response->getLastModifed(),$time,'set/get lastmodified');
}


{
    $response = new Doggy_Dispatcher_Response_Http();
    $response->setHttpResponseCode(404);
    is($response->getHttpResponseCode(),404,'set/getHttpResponseCode');
    try{
        $response->setHttpResponseCode(99);
        fail('setHttpResponseCode:invalid_code');
    }catch (Exception $e){
    }
    try{
        $response->setHttpResponseCode(600);
        fail('setHttpResponseCode:invalid_code');
    }catch (Exception $e){
    }
}


{
    $response = new Doggy_Dispatcher_Response_Http();
    $response->setRedirect('localhost');
    is($response->getHttpResponseCode(),302,'setRedirect');
    $headers = $response->getHeaders();
    ok(in_array(array('name'=>'Location','value'=>'localhost'),$headers),'setRedirect');
    $response->setRedirect('localhost',301);
    $response->setRedirect('localhost',302);
    $response->setRedirect('localhost',303);
    is($response->getHttpResponseCode(),303,'setRedirect');
    $found=0;
    foreach($response->getHeaders() as $h){
        if($h['name']=='Location')$found++;
    }
    is($found,1,'setRedirect');
}

{
    $response = new Doggy_Dispatcher_Response_Http();
    $response->setRawHeader('HTTP/1.0 404 Not Found');
    $headers = $response->getRawHeaders();
    ok(in_array('HTTP/1.0 404 Not Found',$headers),'setRawHeader');
}

{
    $response = new Doggy_Dispatcher_Response_Http();
    $response->setRawHeader('HTTP/1.0 404 Not Found');
    $response->setHeader('Content-Type', 'text/xml');
    $response->clearAllHeaders();
    $headers = $response->getHeaders();
    ok(empty($headers),'clearAllHeaders');
    $headers = $response->getRawHeaders();
    ok(empty($headers),'clearAllHeaders');
}


{
    $response = new Doggy_Dispatcher_Response_Http();
    $expected = 'Hello everyone';
    $response->setBuffer($expected);
    $additional = '; I like more...';
    $response->appendBuffer($additional);
    is($response->getBuffer(),$expected . $additional,'appendBuffer');
}

{
    $response = new Doggy_Dispatcher_Response_Http();
    $response->setHeader('Content-Type', 'text/plain');
    $response->setBuffer('Content');
    $response->appendBuffer('; and more content.');
    $expected = 'Content; and more content.';
    ob_start();
    $response->flushResponse();
    $result = ob_get_clean();
    is($result,$expected,'flush response');
}

?>