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
$vars = array(
    'app.base' => '/home',
    'app.action_name' => 'action',
    'app.url.images' =>'{$app.base}/images',
    'app.url.action' => '{$app.base}/{$app.action_name}' 
    );

is(Doggy_Config::expand_value('/home',$vars),'/home','expand_value/no literal');
is(Doggy_Config::expand_value('{$app.base}/images',$vars),'/home/images','expand_value/literal');
is(Doggy_Config::expand_value('{$app.base}/{$app.action_name}',$vars),'/home/action','expand_value/literal(more)');

// check circle ref
try {
    $vars = array(
        'app.base' => '{$app.base}'
        );
    Doggy_Config::expand_value('{$app.base}',$vars);
    fail('expand_value/circle reference not dectected!');
} catch (Doggy_Exception $e) {
    pass('expand_value/circle reference');
}

try {
    $vars = array(
        'app.base' => '{$app.base2}',
        'app.base2' => '{$app.base}',
        );
    Doggy_Config::expand_value('{$app.base}',$vars);
    fail('expand_value/circle reference not dectected!');
} catch (Doggy_Exception $e) {
    pass('expand_value/circle reference2');
}

// now full test
Doggy_Config::$vars = array();

$config1 = array(
    'app.domain' => 'http://localhost',
    'app.static_domain' => '{$app.domain}',
    'app.app_domain' => '{$app.domain}',
    'app.url.css' => '{$app.static_domain}/css',
    'app.url.action' => '{$app.app_domain}/app/doggy'
    );

$config2 = array(
    'app.domain' => 'http://test.com',
    );

$tmp_file = '/tmp/doggy_test_config.yml';
load_tmp_config($config1,$tmp_file);
load_tmp_config($config2,$tmp_file);

Doggy_Config::expand_all();
is(Doggy_Config::$vars['app.url.css'],'http://test.com/css','expand_all');

function dump_config($value) {
    if (defined('SYCK_OK')) {
        $dump_content = syck_dump($value);
    }
    else {
        $yml = new Spyc();
        $yml->setting_dump_force_quotes = true;
        $dump_content = $yml->dump($value);
    }
    return $dump_content;
}

function load_tmp_config($config,$tmp_path) {
    $dump_content = dump_config($config);
    file_put_contents($tmp_path,$dump_content);
    Doggy_Config::load_file($tmp_path);
    unlink($tmp_path);
}
?>