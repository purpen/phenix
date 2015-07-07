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
class Doggy_Dispatcher_Interceptor_UploadSupportTest_Action extends Doggy_Mock_Action implements Doggy_Dispatcher_Action_Interface_UploadSupport {
    private $_files = array();
    public function setUploadFiles($files){
        $this->_files=$files;
    }
    public function getUploadFiles(){
        return $this->_files;
    }
}

$server = Doggy_Mock_Server::mock('Doggy_Dispatcher_Interceptor_UploadSupportTest_Action');
//mock upload files
$f1 = '/tmp/test_upload1.txt';
$f2 = '/tmp/test_upload2.txt';

file_put_contents($f1,'t1');
file_put_contents($f2,'t1');


$server->upload('file1',$f1);
$server->upload('file2',$f2);
$server->upload('file3','unkonw.files');
$server->invokeInterceptor('Doggy_Dispatcher_Interceptor_UploadSupport');
$action = $server->getAction();
$files = $action->getUploadFiles();
is(count($files),2,'common upload');
foreach($files as $f){
    ok($f['id']=='file1' || $f['id']=='file2','common upload');
}
//test upload multi files
$server->reset();
$server->upload(array('file'=>array($f1,$f2,'unkonw.files')));
$server->invokeInterceptor('Doggy_Dispatcher_Interceptor_UploadSupport');

$action = $server->getAction();
$files = $action->getUploadFiles();
is(count($files),2,'batch upload');
foreach($files as $f){
    ok($f['id']=='file','batch uplaod');
}

@unlink($f1);
@unlink($f2);
?>