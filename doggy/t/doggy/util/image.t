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

$image_file = dirname(__FILE__).'/test.jpg';
$target_file1 = '/tmp/test_thumb1.png';
$target_file2 = '/tmp/test_thumb2.png';
$target_file3 = '/tmp/test_thumb3.png';

if (!extension_loaded('imagick')) {
    plan('skip_all',"can't load imagick.so");
}

{
    $ok = Doggy_Util_Image::make_thumb_file($image_file,400,200,$target_file1);
    ok($ok,'make thumb, cut&reduce');
    $bytes =file_get_contents($target_file1);
    ok(!empty($bytes),'make thumb, THUMB_CROP_RESIZE');
}

{
    $ok = Doggy_Util_Image::make_thumb_file($image_file,400,200,$target_file2,Doggy_Util_Image::THUMB_CROP);
    ok($ok,'make thumb, cut');
    $bytes =file_get_contents($target_file2);
    ok(!empty($bytes),'make thumb, THUMB_CROP');
}

{
    $ok = Doggy_Util_Image::make_thumb_file($image_file,400,200,$target_file3,Doggy_Util_Image::THUMB_RESIZE);
    ok($ok,'make thumb, reduce');
    $bytes = file_get_contents($target_file2);
    ok(!empty($bytes),'make thumb, THUMB_RESIZE');
    
}
@unlink($target_file1);
@unlink($target_file2);
@unlink($target_file3);

?>