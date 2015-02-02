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
class DoggyX_Model_Mock_User extends DoggyX_Model_Lite {
    const STATE_PENDING = 1;
    const STATE_OK = 2;
    protected static $state_labels = array(
        '1' => 'pending',
        '2' => 'ok',
        );
    protected $collection = 'user';
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_CUSTOM;
    protected $schema = array(
        'nickname' => '',
        'state' => self::STATE_PENDING,
        );
    protected $int_fields = array('state');
    protected $required_fields = array('nickname');
    protected function extra_extend_model_row(&$row) {
        $row['state_label'] = self::$state_labels[$row['state'].''];
    }
}
if (!function_exists('is_ref_ok')) {
    function is_ref_ok(&$a, &$b,$msg=null) {
        if (is_object($a) && is_object($b)) {
            return ok($a === $b,$msg);
        }

        $temp_a = $a;
        $temp_b = $b;

        $key = uniqid('is_ref_to', true);
        $b = $key;

        if ($a === $key) $return = true;
        else $return = false;

        $a = $temp_a;
        $b = $temp_b;
        return ok($return,$msg);
    }
}

$user_id = 6;
$user = new DoggyX_Model_Mock_User();
$user->create(array('_id' => $user_id,'nickname' => 'ns'));
$user->create(array('_id' => 7,'nickname' => 'ns2'));

$row1 = & DoggyX_Model_Mapper::load_model($user_id,'DoggyX_Model_Mock_User');
$row2 = & DoggyX_Model_Mapper::load_model($user_id,'DoggyX_Model_Mock_User');

is_ref_ok($row1,$row2,'load_model');
$list = DoggyX_Model_Mapper::load_model_list(array(6,7),'DoggyX_Model_Mock_User');
is_ref_ok($list[0],$row1,'load_model_list');
is($list[1]['nickname'],'ns2','load_model_list');

$user->drop();
?>