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
$options = Doggy_Dt_Options::merge();
{
    $context = new Doggy_Dt_Context(array(),$options);
    $context->set('a','a');
    $context->set('b',5);
    is($context->get_var('a'),'a','get_var/scalar');
    is($context->get_var('b'),5,'get_var/scalar');
    
}
{
    $context = new Doggy_Dt_Context(array(),$options);
    $v = array(
        'a' => 'a',
        'b'=>'b',
        'c'=>array(1,2,3),
        );
    $context->set('v',$v);
    is($context->get_var('v.a'),'a','get_var/array');
    is($context->get_var('v.b'),'b','get_var/array');
    is($context->get_var('v.c.0'),1,'get_var/array');
    is($context->get_var('v.c.1'),2,'get_var/array');
    is($context->get_var('v.c.2'),3,'get_var/array');
    is($context->get_var('v.c.first'),1,'get_var/array:first');
    is($context->get_var('v.c.size'),3,'get_var/array:size');
    is($context->get_var('v.c.length'),3,'get_var/array:length');
    is($context->get_var('v.c.last'),3,'get_var/array:last');
    
}
{
    $context = new Doggy_Dt_Context(array(),$options);
    $v = new Dt_Test_Obj();
    $context->set('v',$v);
    is($context->get_var('v.a'),'a','get_var/obj');
    is($context->get_var('v.b'),'b','get_var/obj');
    is($context->get_var('v.c'),'c','get_var/obj');
    $v->dt_safe = array();
    is($context->get_var('v.c'),null,'get_var/obj:dt_safe');
}
{
    $lookup_table = array(
        array('Dt_Test_Lookup','techo'),
        );
    Doggy_Dt_Context::$lookupTable = $lookup_table;
    $context = new Doggy_Dt_Context(array(),$options);
    is($context->external_lookup('a'),'techo:a','external_lookup');
    Doggy_Dt_Context::$lookupTable = array();
    
}
{
    $context = new Doggy_Dt_Context(array(),$options);
    $context->set('a','a');
    $context->set('b',5);
    is($context->resolve(true),true,'resolve/true');
    is($context->resolve(false),false,'resolve/false');
    is($context->resolve('"ok"'),'ok','resolve/string');
    is($context->resolve('-5'),-5,'resolve/int');
    is($context->resolve(':a'),'a','resolve/scalar');
    is($context->resolve(':b'),5,'resolve/scalar');
    $v = array(
        'a' => 'a',
        'b'=>'b',
        'c'=>array(1,2,3),
        );
    $context->set('v',$v);
    is($context->resolve(':v.a'),'a','resolve/array');
    is($context->resolve(':v.b'),'b','resolve/array');
    is($context->resolve(':v.c.0'),1,'resolve/array');
    $v = new Dt_Test_Obj();
    $context->set('v',$v);
    is($context->resolve(':v.a'),'a','resolve/obj');
    
    Doggy_Dt_Context::$lookupTable = $lookup_table;
    is($context->resolve('abc'),'techo:abc','resolve/external_lookup');
    Doggy_Dt_Context::$lookupTable = array();
}
{
    $filter = array(
        array('|cat',':a'),
        array('|cat',':b'),
        );
    Doggy_Dt::$filters = array('cat'=>'dt_test_cat');
    $context = new Doggy_Dt_Context(array(),$options);
    $context->set('a','a');
    $context->set('b','b');
    is($context->apply_filters(5,$filter),'5ab','apply_filters');
}

function dt_test_cat() {
    $args = func_get_args();
    return implode('',$args);
}


class Dt_Test_Obj {
    public $dt_safe = true;
    public $a = 'a';
    public $b = 'b';
    public function c(){
        return 'c';
    }
}

class Dt_Test_Lookup {
    public static function techo($v,$context) {
        return "techo:".$v;
    }
}
?>