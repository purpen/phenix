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

$test_table = 'doggy_db_test';

$dsn = Doggy_Config::get('app.db.default');

if (empty($dsn)) {
    plan('skip_all', 'app.db.default not setup,please set correctly dns before run test.'); 
}
diag("test dsn:$dsn");
$dba = new Doggy_Db_Driver_Mysql($dsn);

ok($dba->connect(),'test connect');

try {
    $sql = "DROP TABLE  IF EXISTS $test_table";
    $dba->execute($sql);
    $sql = "CREATE TABLE $test_table (t CHAR(50))";
    $dba->execute($sql);
    $sql = "INSERT INTO $test_table values(?)";
    $dba->execute($sql,array('a'));
    $dba->execute($sql,array('b'));
    $dba->execute($sql,array('This\'is T?'));
    $dba->execute($sql,array('This\',DELETE FROM * \"is  T?'));
    $dba->execute($sql,array(5));
    $dba->execute("DROP TABLE $test_table");
    pass('execute test');
} catch(Exception $e) {
    fail('execute test:'.$e->getMesssage());
}

#query
{
    _create_test_table();
    
    $sql = "INSERT INTO $test_table values(?)";
    $dba->execute($sql,array('a'));
    $dba->execute($sql,array('b'));
    $dba->execute($sql,array('This\'is T?'));
    $dba->execute($sql,array('This\',DELETE FROM * \"is  T?'));
    $dba->execute($sql,array(5));
    
    for($i=0;$i<20;$i++){
        $dba->execute($sql,array($i));
    }
    
    $test_name = 'test fetch single row';
    $row = $dba->query("select * from $test_table",1,1);
    is(count($row),1,$test_name);
    is($row[0]['t'],'a',$test_name);
    
    $test_name = 'test fetch 2 rows';
    $row = $dba->query("select * from $test_table",2,1);
    is(count($row),2,$test_name);
    is($row[1]['t'],'b',$test_name);
    
    $test_name = 'test fetch last rows';
    $row = $dba->query("select * from $test_table",2,2);
    is(count($row),2,$test_name);
    is($row[1]['t'],'This\',DELETE FROM * \"is  T?',$test_name);
    
    $test_name = 'fetch all rows';
    $sql = "select * from $test_table";
    $row = $dba->query($sql);
    is(count($row),25,$test_name);
    
    //wrong page,0,meant all
    $test_name = 'test paged, 0, all';
    $row = $dba->query($sql,-1,0);
    is(count($row),25,$test_name);
    //wrong size,meant all
    $test_name = 'test paged, -1,all';
    $row = $dba->query($sql,-1);
    is(count($row),25,$test_name);
    
    //wrong page,morn than total records,return empty
    $test_name = 'test paged,beyond totoal,empty';
    $row = $dba->query($sql,40,2);
    is(count($row),0,$test_name);
    
    $test_name = 'test paged,-1,1,all';
    $row = $dba->query($sql,-1,1);
    is(count($row),25,$test_name);
    
    $test_name = 'test paged,6,5,limited size';
    $row = $dba->query($sql,6,5);
    is(count($row),1,$test_name);
    
} 

# get_tables
{
    $dba->execute('DROP TABLE  IF EXISTS doggy_test_t1');
    $dba->execute('DROP TABLE  IF EXISTS doggy_test_t2');
    $dba->execute('CREATE TABLE doggy_test_t1 (t CHAR(50))');
    $dba->execute('CREATE TABLE doggy_test_t2 (t CHAR(50))');
    
    $table_list = $dba->get_tables();
    ok(in_array('doggy_test_t1',$table_list) && in_array('doggy_test_t2',$table_list) ,'get_tables');
    $dba->execute('DROP TABLE doggy_test_t2');
    $table_list = $dba->get_tables();
    ok(!in_array('doggy_test_t2',$table_list),'get_tables,refresh');
    $dba->execute('DROP TABLE doggy_test_t1');
    $table_list = $dba->get_tables();
    ok(!in_array('doggy_test_t1',$table_list),'get_tables,refresh');
    
}

# FieldMetaList
{
    $dba->execute("DROP TABLE  IF EXISTS $test_table");
    $dba->execute("CREATE TABLE $test_table (t CHAR(50),t2 int(11) ,t3 date,t4 datetime,t5 varchar(100) )");
    $fields = $dba->get_fields($test_table);
    var_dump($fields);
    is($fields,array(
        't' => array('name'=>'t','type'=>'S','size'=>50,'pk'=>false,'null'=>true,'auto_inc'=>false,'default'=>null),
        't2' => array('name'=>'t2','type'=>'N','size'=>11,'pk'=>false,'null'=>true,'auto_inc'=>false,'default'=>null),
        't3' => array('name'=>'t3','type'=>'D','size'=>-1,'pk'=>false,'null'=>true,'auto_inc'=>false,'default'=>null),
        't4' => array('name'=>'t4','type'=>'T','size'=>-1,'pk'=>false,'null'=>true,'auto_inc'=>false,'default'=>null),
        't5' => array('name'=>'t5','type'=>'S','size'=>100,'pk'=>false,'null'=>true,'auto_inc'=>false,'default'=>null),
        ),
        'get_fields');
    _clean_test_table();
}
# genSeq/dropSeq
{
    $i=1;
    is($dba->gen_seq($test_table),$i,'gen_seq ... ori');
    $i++;
    is($dba->gen_seq($test_table),$i,'gen_seq ... inc');
    $dba->drop_seq($test_table);
    is($dba->gen_seq($test_table),1,'drop_seq');
    $dba->drop_seq($test_table);
}

function _create_test_table() {
    global $dba,$test_table;
    $sql = "DROP TABLE  IF EXISTS $test_table";
    $dba->execute("DROP TABLE IF EXISTS $test_table");
    $dba->execute("CREATE TABLE $test_table (t CHAR(50))");
}

function _clean_test_table() {
    global $dba,$test_table;
    $dba->execute("DROP TABLE IF EXISTS $test_table");
}

?>