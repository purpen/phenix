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

class DoggyX_Model_Mock_Art extends DoggyX_Model_Lite {
    protected $collection = 'art';
    protected $schema = array(
        'user_id' => 0,
        'title' => '',
        'asset_list' => array(),
        'cover_id' => null,
        );
    protected $required_fields = array('title','user_id');
    protected $joins = array(
        'user' => array('user_id' => 'DoggyX_Model_Mock_User'),
        'assets' => array('asset_list' => 'DoggyX_Model_Mock_Asset'),
        'cover' => array('cover_id' => 'DoggyX_Model_Mock_Asset'),
        );
    protected function extra_extend_model_row(&$row) {
        if (!empty($row['asset_list']) && !empty($row['cover_id'])) {
            for ($i=0; $i < count($row['assets']); $i++) { 
                if ($row['assets'][$i]['_id'] == $row['cover_id']) {
                    $row['assets'][$i]['is_cover'] = true;
                }
            }
        }
    }
}

class DoggyX_Model_Mock_Asset extends DoggyX_Model_Lite {
    protected $collection = 'asset';
    protected $schema = array(
        'art_id' => null,
        'file_path' => '',
        );
}

$user_id = 6;
$user = new DoggyX_Model_Mock_User();
$user->create(array('_id' => 6,'nickname' => 'ns'));

$art = new DoggyX_Model_Mock_Art();
$art->create(array('user_id' => $user_id,'title' => 'test foo'));
$art_id = $art->id.'';

$asset = new DoggyX_Model_Mock_Asset();
for ($i=0; $i < 5; $i++) { 
    $asset->create(array('file_path' => $i.'.jpg','art_id' => $art_id));
    $asset_list[] = $asset->id;
}

$art->update_set($art_id, array('asset_list' => $asset_list,'cover_id' => $asset_list[0]));
//

$art_info = $art->extend_load($art_id);
is($art_info['user']['nickname'],'ns','orm/join/one-one');
is(count($art_info['assets']),5,'orm/one-many');
is($art_info['assets'][4]['file_path'],'4.jpg','orm/nested load');
is($art_info['cover']['file_path'],'0.jpg','orm/nested load');
is($art_info['assets'][0]['is_cover'],true,'orm/extra_extend_model_row');
is($art_info['user']['state_label'],'pending','orm/extra_extend_model_row/virtual field');
$art->drop();
$asset->drop();
$user->drop();
?>