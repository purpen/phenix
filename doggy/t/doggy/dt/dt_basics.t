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
    $dt = new Doggy_Dt();
    ok($dt instanceof Doggy_Dt);
}

class Doggy_Dt_Tag_Test extends Doggy_Dt_Tag {
    public function render($contxt, $stream) {
        $stream->write('ok');
    }
}
class Doggy_Dt_TestFilter implements Doggy_Dt_FilterLib {
    public static function t1($v) {
        # code...
    }
    public static function t2($value='') {
        # code...
    }
}

{
    Doggy_Dt::$tags = array();
    Doggy_Dt::add_tag('test');
    is(Doggy_Dt::$tags['test'],'Doggy_Dt_Tag_Test','add_tag');
}

{
    Doggy_Dt::$filters = array();
    Doggy_Dt::add_filter('Doggy_Dt_TestFilter');
    Doggy_Dt::add_filter('date');
    
    is(Doggy_Dt::$filters['t1'],array('Doggy_Dt_TestFilter','t1'),'add_filter');
    is(Doggy_Dt::$filters['t2'],array('Doggy_Dt_TestFilter','t2'),'add_filter');
    is(Doggy_Dt::$filters['date'],'date','add_filter,php builtin');
}
{
    $extension = array(
    'test_lib'=>array(
        'tags'=> array('test'=>'Doggy_Dt_Tag_Test'),
        'filters'=>array()
        )
    );
    Doggy_Config::set('app.dt.extension_lib',$extension);
    Doggy_Dt::load('test_lib');
    is(Doggy_Dt::$tags['test'],'Doggy_Dt_Tag_Test','load');
}
?>