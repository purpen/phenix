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
    ok(Doggy_Dt_Evaluator::gt(6,5),'gt');
    ok(Doggy_Dt_Evaluator::ge(10,10),'ge');
    ok(Doggy_Dt_Evaluator::lt(10,11),'lt');
    ok(!Doggy_Dt_Evaluator::le(12,11),'le');
    ok(!Doggy_Dt_Evaluator::eq(2,5),'eq');
    ok(Doggy_Dt_Evaluator::ne(2,5),'ne');
    ok(Doggy_Dt_Evaluator::not_(false),'not_');
    ok(Doggy_Dt_Evaluator::and_(5>3,4<10),'and_');
    ok(Doggy_Dt_Evaluator::or_(5>3,4>10),'or_');
}

$options = Doggy_Dt_Options::merge();
$context = new Doggy_Dt_Context(array(),$options);
$context->set('a',5);
$context->set('b',4);
$context->set('c',true);
$context->set('d',false);

diag('set a=5 b=4 c=true d=false');

{
    
    $args = array(':a');
    is(Doggy_Dt_Evaluator::exec($args,$context),5,'a');
}    
{   
    $args = array();
    $args[0] = array(
        'operator'=>'not');
    $args[1] = ':d';
    ok(Doggy_Dt_Evaluator::exec($args,$context),'not d');
}
{
    $args = array();
    $i=0;
    $args[$i++] = ':a';
    $args[$i++] = array(
        'operator'=>'gt');
    $args[$i++] = ':b';
    ok(Doggy_Dt_Evaluator::exec($args,$context),'a>b');
}
{
    $args = array();
    $i=0;
    $args[$i++] = ':a';
    $args[$i++] = array(
        'operator'=>'lt');
    $args[$i++] = ':b';
    ok(!Doggy_Dt_Evaluator::exec($args,$context),'a<b');
}

{
    $args = array();
    $i=0;
    $args[$i++] = ':a';
    $args[$i++] = array(
        'operator'=>'eq');
    $args[$i++] = ':b';
    ok(!Doggy_Dt_Evaluator::exec($args,$context),'a==b');
}

{
    $args = array();
    $i=0;
    $args[$i++] = ':a';
    $args[$i++] = array(
        'operator'=>'ne');
    $args[$i++] = 10;
    ok(Doggy_Dt_Evaluator::exec($args,$context),'a != 10');
}

#todo
todo_start('multi-expression');
{
    $args = array();
    $i=0;
    $args[$i++] = ':a';
    $args[$i++] = array(
        'operator'=>'ne');
    $args[$i++] = 10;
    $args[$i++] = array(
        'operator'=>'and_');
    $args[$i++] = ':a';
    $args[$i++] = array(
        'operator'=>'lt');
    $args[$i++] = 8;
        
    ok(Doggy_Dt_Evaluator::exec($args,$context),'a != 10 && a<8');
}
todo_end();

?>