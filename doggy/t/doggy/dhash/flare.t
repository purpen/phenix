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
$host = getenv('TEST_FLARE_HOST');
if (empty($host)) {
    plan('skip_all','envirorment variable:TEST_FLARE_HOST not defined.');
}

$options = array('namespace'=>'test','host'=>$host);

$hash = new Doggy_DHash_Flare($options);
{
    $hash->k1 = 5;
    $hash->k2 = 3;
    is($hash->k1,5,'property');
    is($hash->k2,3,'property');
    
    $hash->set('k3','k3');
    is($hash->get('k3'),'k3','set/get');
    
    //check consisten
    $hash2 = new Doggy_DHash_Flare($options);
    is($hash->k3,'k3','check consisten');
}
{
    $hash3 = new Doggy_DHash_Flare($options);
    $hash3->k4 = 0;
    is($hash3->inc('k4'),1,'inc');
    is($hash3->inc('k4'),2,'inc');
    is($hash3->dec('k4'),1,'dec');
}
?>