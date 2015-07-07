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

$test_dir = '/tmp/test_provider_root';


$test_options = array(
    'root' =>  $test_dir,
    'root_url'  => "file://$test_dir",
    'hash_dir' => false,
    );
    
$provider = new Doggy_Storage_Provider_FileSystem($test_options);
{
    $provider->store('t1.txt','t1.txt');
    is($provider->get('t1.txt'),'t1.txt','store');
}
{
    $tmp_file = $test_dir.'/tmp.txt';
    file_put_contents($tmp_file,'123');
    $provider->storeFile('t2.txt',$tmp_file);
    unlink($tmp_file);
    is($provider->get('t2.txt'),'123','storeFile');
}
#exists
{
    ok($provider->exists('t2.txt'),'exists');
    ok($provider->exists('t1.txt'),'exists');
    ok(!$provider->exists('t3.txt'),'exists');
}
#copy
{
    $provider->copy('t2.txt','t3.txt');
    ok($provider->exists('t3.txt'),'copy');
    ok($provider->exists('t3.txt'),'copy');
    is($provider->get('t3.txt'),$provider->get('t2.txt'),'copy');
    
}
#rename
{
    $provider->rename('t3.txt','t4.txt');
    ok(!$provider->exists('t3.txt'),'rename');
    is($provider->get('t4.txt'),$provider->get('t2.txt'),'renmae');
}
#path
{
    $path = $provider->getPath('t4.txt');
    $content = file_get_contents($path);
    is($content,$provider->get('t4.txt'),'getPath');
}
#getUri
{
    $uri = $provider->getUri('t4.txt');
    $content2 = file_get_contents($uri);
    isnt($uri,$path,'getUri');
    is($content2,$content,'getUri');
}

$provider->delete('t1.txt');
$provider->delete('t2.txt');
$provider->delete('t3.txt');
$provider->delete('t4.txt');

if (file_exists($test_dir)) {
    rmdir($test_dir);
}

{
    $test_options = array(
        'root' =>  $test_dir,
        'root_url'  => "file://$test_dir",
        'hash_dir' => true,
        );

    $provider2 = new Doggy_Storage_Provider_FileSystem($test_options);

    $provider2->store('t1.txt','t1.txt');
    is($provider2->get('t1.txt'),'t1.txt','store /hash');
    $provider->delete('t1.txt');
}
if (file_exists($test_dir)) {
    `rm -rf $test_dir`;
}
?>