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
class FooError extends DoggyX_Model_Mongo_Base {
    protected $collection = '';
}

try {
    $foo = new FooError();
    fail('null collection should throw model exception');
} catch (Doggy_Model_Exception $e) {
    pass('null collection check');
}

class Foo extends DoggyX_Model_Mongo_Base{
    protected $collection = 'test_foo';
    protected $schema = array(
        'name' => null,
        'age' => null,
        );
    protected $required_fields = array('name','age');
}

$base = new DoggyX_Model_Mongo_Base();

$row = array(
    'name' => 't1',
    'age'  => 25,
    'tags' => array('t1','t2'),
    );
ok(!$base->is_saved(),'is_saved');

$base->insert($row);
ok($base->is_saved(),'is_saved');
$id = $base->id;
ok($id instanceof MongoId,'create/id check');
ok(isset($base->created_on),'create/auto_timestamp');

{
    $base->reset();
    ok(!$base->is_saved(),'reset');
}

{
    $row = $base->load($id);
    is($row['name'],'t1','load');
    is($base->name,'t1','load');
    $base->reset();
    $id_s = "$id";
    $row = $base->load($id_s);
    is($base->name,'t1','load/string');
    $base->reset();
}
{
    $row = $base->first();
    is($row['name'],'t1','first/insert');
}

{
    is($base->count(),1,'count');
    $base->create(array('name' => 't2'));
    $id2 = $base->id;
    $base->create(array('name' => 't3'));
    $base->create(array('name' => 't4'));
    is($base->count(),4,'count');
    is($base->count(array('name' => 't4')),1,'count/criteria');
}
//update/update_set
{
    $base->update_set(array('name' => 't1'),array('age' => 27));
    $row = $base->load($id);
    is($row['age'], 27,'update');
    $base->update_set(array('name' => 't1'),array('age' => 28,'pet' => 'dog'),true);
    $row = $base->load($id);
    
    ok($row['age'] == 28 && $row['pet'] == 'dog' && $row['tags'] == array('t1','t2'),'update_set/upsert');
    ok(isset($row['updated_on']),'update_set/auto_update_timestamp');
    
    
    $base->update($id, array('age' => 29));
    $row = $base->load($id);
    ok($row['age'] == 29 && !isset($row['pet']),'update');
}
{
    $base->inc($id,'seq');
    $row = $base->load($id);
    is($row['seq'],1,'inc');
    $base->inc($id,'seq',2);
    $row = $base->load($id);
    is($row['seq'],1+2,'inc');
    $base->dec($id,'seq');
    $row = $base->load($id);
    is($row['seq'],2,'dec');
    $base->dec($id,'seq',2);
    $row = $base->load($id);
    is($row['seq'],0,'dec');
    
}
//misc stuff
{
    $base->install_sequence();
    is($base->next_seq_id('art'),1,'next_seq_id(art)');
    is($base->next_seq_id('user'),1,'next_seq_id(user)');
    is($base->next_seq_id('art'),2,'next_seq_id(art)');
    is($base->next_seq_id('user'),2,'next_seq_id(user)');
    is($base->next_seq_id('art'),3,'next_seq_id(art)');
    is($base->next_seq_id('user'),3,'next_seq_id(user)');
    
    $base->set_seq_val('mmmx',20);
    is($base->next_seq_id('mmmx'),21,'set_seq_val');
}

class FooX extends DoggyX_Model_Mongo_Base {
    protected $collection = 'foo';
    protected $schema = array('k' => null,'a' => null);
    protected $mongo_id_style = DoggyX_Model_Mongo_Base::MONGO_ID_SEQ;
}

$foox = new FooX();
$i = 1;
$foox->create(array('k' => $i));
is($foox->id,$i++, 'id_seq_style');
$foox->create(array('k' => $i));
is($foox->id,$i++, 'id_seq_style');

$row = array('k' => 100,'a' => 'a','b' => 'b');

$foox->reset();
$foox->apply_schema_data($row);
$foox->save();

$id2 = $foox->id;
$row2 = $foox->load($id2);
ok(!isset($row2['b']),'apply_schema_data');



$row2 = array('k' => 300,'id' => "$id2");
$foox->reset();
$foox->a = 'aaa';
$foox->apply_and_save($row2);
$id3 = $foox->id;
$row2 = $foox->load($id3);
ok($id2 == $id3 && $row2['k'] == 300,'apply_and_save');



$data = $foox->filter_schema_data(array('id' => 10,'k' => 100,'a' => 'a1','b' => 'b1','c' => 'c2'));
is($data,array('k' => 100,'a' => 'a1',),'filter_schema_data');

$data = $foox->filter_schema_data(array('id' => 10,'k' => 100,'a' => 'a1','b' => 'b1','c' => 'c2'),array('id'),true);
is($data,array('_id'=>10,'a' => 'a1','k' => 100),'filter_schema_data');


$foox->apply_and_update(array('id' => $id3,'a' => 'a1','b' => 'b1','c' => 'c2'));

$row = $foox->load($id3);

ok($row['k'] == 300 && $row['a'] == 'a1','apply_and_update' );

// unset,new in mongodb 1.3+
$base->drop();
$base->insert(array('tags' => array(
        't' => 10,
        't2' => 3
        )
    ));

$id = $base->id;
$base->update_unset($id,array('tags.t' => 1));
$row = $base->load($id);
ok(!isset($row['tags']['t']),'unset hash');

$base->insert(array('comments' => array(
    array('name' => 'p1','rank' => 80),
    array('name' => 'p2','rank' => 81,'verfied' => 1),
    )));

$id = $base->id;
$base->update_unset($id,array('comments.1.verfied' => 1,'comments.0' => 1));

$row = $base->load($id);

ok($row['comments'][0]===null,'unset array/null');
is($row['comments'][1],array('name' => 'p2','rank' => 81),'unset array/nested hash key');

{
    $foo = new Foo();
    try {
        $foo->create(array());
        fail('validate required fields');
    } catch (Doggy_Model_ValidateException $e) {
        pass('validate required fields');
    }
    $foo->drop();
} 


////clean
$foox->drop();
$base->drop();
$base->drop_seq_collection();
?>