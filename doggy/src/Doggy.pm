package Doggy;
use strict;
use Getopt::Long;
use File::Path;
use File::Spec;
use File::Basename;
use File::Copy;
use Cwd;
use Carp;
use Term::ANSIColor qw(:constants);
use File::Temp qw(tempfile tempdir);
use YAML;
use Data::Dumper;
use constant {
                CONFIG_RUNTIME_FILENAME => 'doggy_app.rc',
                DEV_RC => 'rc.dev.yml',
                DEPLOY_RC => 'rc.deploy.yml',
            };
            
use Shell qw(php env prove);

my $project_mark = '';
my %project_path = ();
my %doggy_path = ();
my $child_pid;

our $VERSION='1.3.x-dev';

my %doggy_helper = (
    MK_TEST => 'mk_test.php',
    MK_CONFIG => 'mk_runtime_config.php',
    MK_MODEL => 'mk_model.php',
    MK_MODEL_AR => 'mk_model_ar.php',
    PHPT_RUNNER => 'run-tests.php',
    );

sub sigint {
    $SIG{INT} = \&sigint;
    if ($child_pid) {
        kill INT => $child_pid;
    }
}

sub load_doggy {
    my($root) = shift;
    croak "FATAL ERROR:doggy root is NULL!\n" unless $root;
    $doggy_path{root}           = $root;
    $doggy_path{src}            = File::Spec->catdir($doggy_path{root},'src');
    $doggy_path{vendor}         = File::Spec->catdir($doggy_path{root},'vendor');
    $doggy_path{script_root}    = File::Spec->catdir($doggy_path{root},'scripts');
    $doggy_path{version}        = File::Spec->catfile($doggy_path{root},'VERSION');
    if ( -e $doggy_path{version}) {
        $VERSION = `cat $doggy_path{version}`
    }
    $doggy_path{compiled_class}   = File::Spec->catfile($doggy_path{root},'compiled','doggy.compiled.class');
    $doggy_path{compiled_class_list}   = File::Spec->catfile($doggy_path{root},'compiled','doggy.class.list');
}


sub do_task {
    my($task , @args) = @_;
    my $help = '';
    my $version ='';
    my $project_dir='';
    
    &help_message && return  unless $task;
    GetOptions('help'=>\$help,'version'=>\$version,'p' => \$project_dir );
    
    help_message()   if $help;
    &show_version && return  if $version;
    
    if ($project_dir) {
        if (-d $project_dir && -e $project_dir) {
            $project_path{root} = $project_dir;
        }
        else {
            help_message("Target dir:$project_dir is'nt a valid project directory.")
        }
    }
    else {
        $project_path{root} = getcwd;
    }
    
    
    $task =~ tr /-/_/ ;
    
    help_message("Invalid command:$task")  unless defined &$task;
    
    #init project layout
    $project_path{src}      = File::Spec->catdir($project_path{root},'src'); 
    $project_path{test}     = File::Spec->catdir($project_path{root},'t');
    $project_path{config}     = File::Spec->catdir($project_path{root},'config');
    $project_path{dev_root} = File::Spec->catdir($project_path{root},'dev_root');
    $project_path{vendor} = File::Spec->catdir($project_path{root},'vendor');
    $project_path{compiled_class} = File::Spec->catfile($project_path{root},'compiled','app.compiled.class');
    $project_path{compiled_class_list} = File::Spec->catfile($project_path{root},'compiled','app.class.list');
    $project_path{php_class_path} = $project_path{vendor}.':'.$project_path{src}.':'.$project_path{test}.":".$doggy_path{src};
    
    
    print CYAN,">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n";
    print MAGENTA,"  Doggy framework ",RED,$VERSION,RESET;
    print CYAN,">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>\n",RESET;
    # print "project_root:$project_path{root}\n";
    # print "dev_root:$project_path{dev_root}\n";
    # print "class_path:$project_path{php_class_path}\n";
    # print "-------------------------------------------\n";
    no strict 'refs';
    return &$task(@args);
}

sub _touch {
    my $path = shift;
    open(my $fh, '+>',$path) or croak "Failed touch $path";
    my $atime = time;
    # print $fh $atime;
    close $fh;
    utime $atime,$atime,$path;
}

sub _invoke_php_helper {
    my($helper_name,@args) = @_;
    my $include_path = "-d include_path=$project_path{php_class_path}";
    
    my $php_helper_file = File::Spec->catfile($doggy_path{script_root},$helper_name);
    my $result = php($include_path,$php_helper_file,@args);
    if ($?==0) {
        print GREEN,$result,RESET;
    }
    else {
        print RED,$result,RESET;
    }
}
sub _check_php_syntax {
    my ($php_file) = @_;
    my $options = "-l -d include_path=$project_path{php_class_path}";
    print "check syntax $php_file ...";
    my $result = php($options,$php_file);
    if($?==0){
        print GREEN,"ok\n",RESET;
        return 1;
    }
    else {
        print RED,"failed:$result\n",RESET;
        return 0;
    }
}

sub init {
    my $mask = umask 0022;
    
    my $init_flag = File::Spec->catfile($project_path{dev_root},'.initialized');
    if (-e $init_flag) {
        return 1;
    }
    
    mkpath($project_path{dev_root});
    umask 0000;
    mkpath(File::Spec->catdir($project_path{dev_root},'var'));
    mkpath(File::Spec->catdir($project_path{dev_root},'var','cache'));
    mkpath(File::Spec->catdir($project_path{dev_root},'var','tmp'));
    mkpath(File::Spec->catdir($project_path{dev_root},'logs'));
    
    symlink "$project_path{root}/data/templates","$project_path{dev_root}/templates";
    symlink "$project_path{root}/data/web","$project_path{dev_root}/web";
    
    
    umask $mask;
    
    my $deploy_dir = File::Spec->catdir($project_path{root},'deploy');
    
    unless(-e "${deploy_dir}/dev.yml") {
        print "create dev.yml from dev.yml.example\n";
        copy("${deploy_dir}/dev.yml.example","${deploy_dir}/dev.yml") or print RED,"create dev.yml failed,please check.\n",RESET;
    }
    else {
        print "dev.yml found,has been created.\n";
    }

    unless(-e "$deploy_dir/test.yml") {
        print "create test.yml from test.yml.example\n";
        copy("${deploy_dir}/test.yml.example","${deploy_dir}/test.yml") or print RED,"create test.yml failed,please check.\n",RESET;        
    }
    else {
        print "test.yml found, has been created.\n";
    }
    unless (-e "$deploy_dir/nginx_deploy.conf.example") {
        open FH,">$deploy_dir/nginx_deploy.conf.example" or fatal("Could'nt create file:$deploy_dir/nginx_deploy.conf.example.");
        print FH "server {
                listen       80;
                #edit this!
                server_name  localhost;
                charset utf-8;
                index  app;
                root $project_path{dev_root}/web;
                location  /app {
                    fastcgi_split_path_info ^(/app)(.*)\$;
                    fastcgi_intercept_errors on;
                    include fastcgi_params;
                }
                location /__file_result__/ {
                    internal;
                    alias /;
                }
        }\n";
        close FH;
    }
    else {
        print "nginx_deploy.conf.example found,skip.\n";
    }

    print "nginx deploy file created as: deploy/nginx_deploy.conf.example\n";
    print "edit it for your live develop enviorment.\n";
    print "\n";
    print "Ok,Your dev website root is:\n";
    print "$project_path{dev_root}\n";
    print "**************************************************\n";
    print "Last, your might edit Test setting file:\n";
    print RED,"deploy/test.yml\n",RESET;
    print "and DEV setting file:\n";
    print CYAN,"deploy/dev.yml\n",RESET;
    print "**************************************************\n";
    print GREEN,"Congratulations! The project develop enviroment has been created for you.\nEnjoy!\n",RESET;
    print "\n\n";
    
    _touch $init_flag;
    
    return 1;
}



sub clean {
    rmtree($project_path{dev_root});
    print GREEN,"ok,project dev enviroment has been cleaned.\n",RESET;
}

sub _read_deploy_schema {
    my $schema_file = shift;
    return YAML::LoadFile($schema_file);
}

sub _read_file {
    my $input_file = shift;
    open(my $input_fh, "<", $input_file ) || die "can't read file $input_file \n";
    my $text = join('', <$input_fh>);
    close($input_fh);
    return $text;
}

sub fatal{
    print RED,"FATAL Error:",shift,RESET,"\n";
    exit 1;
}
sub deploy {
    
    my $schema_name = shift;
    $schema_name = 'prod' unless $schema_name;
    my $schema_path = File::Spec->catfile($project_path{root},'deploy',"${schema_name}.yml");
    fatal "Can't find deploy schema, please create schema file named:'${schema_name}.yml' under deploy/ directory.!" unless -e $schema_path;
    _deploy_schema($schema_path);
   
}

sub compile_doggy {
    my $class_list = shift;
    unless ($class_list) {
        $class_list = $doggy_path{compiled_class_list};
    }
    else {
        $class_list = File::Spec->catfile($doggy_path{root},'compiled',"doggy.${class_list}.list");
    }
    
    
    fatal("missing ".$class_list ) unless -e $class_list;
    _compile_class_file($class_list,$doggy_path{compiled_class},$doggy_path{src});
}

sub compile_app {
    fatal("missing ".$project_path{compiled_class_list}) unless -e $project_path{compiled_class_list};
    _compile_class_file($project_path{compiled_class_list},$project_path{compiled_class},$project_path{src});
}

sub _compile_class_file {
    my $input_list = shift;
    my $output_class = shift;
    my $source_root = shift;
    
    
    open F,"<$input_list" or fatal("can't read class list file from :$input_list \n");
    open T,">$output_class" or fatal("can't create class output:$output_class \n");
    my $content;
    my %compiled_hash;
    while (<F>) {
        chomp;
        my $source = $_;
        if (exists $compiled_hash{$source}) {
            print YELLOW,"$source is duplicated,skip\n",RESET;
            next;
        }
        $compiled_hash{$source} = 1;
        $source =~ tr |_|\/|;
        $source = File::Spec->catfile($source_root,"${source}.php");
        if (-e $source) {
            print "append $source ..\n";
            $content .= `cat $source`;
        }
        else {
            print YELLOW,"$source not exists,skip\n",RESET;
        }
    }
    $content =~ s/\?><\?php//g;
    if ($content) {
        print T $content;
    }
    close F;
    close T;
    print "ok,compiled class into:$output_class.\n";
    
}

sub _deploy_schema {
    my $schema_file = shift;
    my $is_dev_mode = shift;
    my $schema_conf_ref = _read_deploy_schema($schema_file);
    
    my $deploy_root = $schema_conf_ref->{'deploy.root'};
    
    if ($is_dev_mode) {
        $deploy_root = $project_path{dev_root};
    }
    
    
    print "------------DEPLOY INFORMATION---------------\n";
    print " install root: $deploy_root\n";
    print "\n\n";
    
    unless ($is_dev_mode) {
        print "install application data stuff...\n ";
        eval{
            umask 0000;
            mkpath("${deploy_root}/templates") unless -e "${deploy_root}/templates";
            mkpath("${deploy_root}/web") unless -e "${deploy_root}/web";
            mkpath("${deploy_root}/var/tmp") unless -e "${deploy_root}/var/tmp";
            mkpath("${deploy_root}/var/cache") unless -e "${deploy_root}/var/cache";
            mkpath("${deploy_root}/logs") unless -e "${deploy_root}/logs";
        };
        if ($@) {
            fatal("create deploy dirs failed:$@");
        }
        `cd $project_path{root}/data &&  find . | cpio -p ${deploy_root}`;
    }
    
    print "install runtime config ...\n";
    my $app_rc_file = File::Spec->catfile($deploy_root,'var',CONFIG_RUNTIME_FILENAME);
    _merge_runtime_config($app_rc_file, $schema_file);
    
    print "install server bootstrap ...\n";
    
    my $boostrap_name = ($schema_conf_ref->{'deploy.bootstrap_name'} || 'app');
    my $bootstrap_path = File::Spec->catfile($deploy_root,'web',$boostrap_name);
    _generate_bootstrap($bootstrap_path,$app_rc_file,$deploy_root);
    print "bootstrap installed into:$bootstrap_path\n";
    print "------------DEPLOY DONE------------------\n";
    
}

sub _generate_bootstrap {
    my $bootstrap_path = shift;
    my $app_rc = shift;
    my $deploy_root = shift;
    my $bootstrap_extra_file = shift;
    $bootstrap_extra_file = File::Spec->catfile($project_path{root}, 'deploy', 'app.bootstrap_extras.php') unless $bootstrap_extra_file;
    open FH,">$bootstrap_path" or fatal("Could'nt create file:$bootstrap_path.");
    my $stamp = localtime();
    print FH <<"EOF";
<?php
//deployed on: $stamp
define('DOGGY_VERSION','$VERSION');
define('DOGGY_APP_ROOT','$deploy_root');
define('DOGGY_APP_CLASS_PATH','$project_path{vendor}:$project_path{src}');
require '$doggy_path{src}/Doggy.php';
// ---------------BEGIN INCLUDE deploy/app.include.php
EOF

    if (-e $bootstrap_extra_file) {
        my $bootstrap_content = _read_file($bootstrap_extra_file);
        print FH "$bootstrap_content" if $bootstrap_content;
    }
    print FH "\n\n";
    print FH "// ---------------END INCLUDE deploy/app.include.php\n\n";
    print FH "\@require '$doggy_path{compiled_class}';\n" if -e $doggy_path{compiled_class};
    print FH "\@require '$project_path{compiled_class}';\n" if -e $project_path{compiled_class};
    print FH "\@require '$app_rc';\n";
    print FH "Doggy_Dispatcher_Server::run();\n";
    print FH "?>";
    close FH;
}
sub dev {
    init;
    my $dev_schema_file = File::Spec->catfile($project_path{root},'deploy','dev.yml');
    fatal "Can't find dev deploy schema, please create schema file named:'dev.yml' under deploy/ directory.!" unless -e $dev_schema_file;
    _deploy_schema($dev_schema_file,1);
}

sub _merge_runtime_config {
    my ($output_merge_config_file,$custom_config_file) = @_;
    _invoke_php_helper($doggy_helper{MK_CONFIG},$project_path{config},$custom_config_file,$output_merge_config_file) ;
}

sub mk_model {
    init;
    my ($class_name) = shift;
    my $model_name = shift;
    
    unless ($class_name) {
        help_message("model class_name is NULL");
    }

    unless($model_name) {
        my @t = split('::',$class_name);
        $model_name  = lc(pop @t);
    }
    
    $ENV{'DOGGY_APP_ROOT'} = $project_path{dev_root};
    my $include_path = "-d include_path=$project_path{php_class_path}";
    my $helper = File::Spec->catfile($doggy_path{script_root},$doggy_helper{MK_MODEL});
    print "read db table schema: $model_name  ...";
    my $result = `php $include_path $helper $model_name`;
    if ($?==0) {
        print "ok.\n";
    }
    else {
        print "failed.\n";
        print $result;
        fatal('read db table schema failed.');
    }
    
    my $model_class_file = File::Spec->catfile($project_path{src},join('/',map{ucfirst} split('::',$class_name)).'.php');
    my $model_php_class_name =  join('_',map{ucfirst} split('::',$class_name));
    
    unless (-e $model_class_file) {
        mkpath(dirname($model_class_file));
        open FH,">$model_class_file" or fatal("Can't create file:$model_class_file");
        print FH <<"EOF";
<?php
/**
 * Model $model_name
 *
 */
class ${model_php_class_name} extends Doggy_Model_Lite \{
    protected \$model_name = '$model_name';
    protected \$defaults = array();
    /**
     * model validation
     *
     * \@return bool
     * \@throws Doggy_Model_ValidateException
     */
    protected function validate() \{
        //new mode
        if (!\$this->saved) \{
            
        }
        // update mode
        else \{
            
        }
        return true;
    }
}
EOF
        print FH "?>";
        close(FH);
        print GREEN,'ok.skeleton class generated into ',$model_class_file,"\n",RESET;
    }
    else {
        print "model php skeleton exists,skip.\n";
    }
    #update meta yml
    print "generate/update table meta data....";
    my $model_meta_file = File::Spec->catfile($project_path{root},'model_meta',join('.',map{lc} split('::',$class_name)).'.yml');
    mkpath(dirname($model_meta_file));
    open FH,">$model_meta_file" or fatal("Can't create file:$model_meta_file");
    print FH $result;
    print FH "\n";
    close(FH);
    print GREEN,"ok. meta update into ",$model_meta_file,"\n",RESET;
}

sub mk_model_x {
    init;
    my ($class_name) = shift;
    my $model_name = shift;
    
    unless ($class_name) {
        help_message("model class_name is NULL");
    }

    unless($model_name) {
        my @t = split('::',$class_name);
        $model_name  = lc(pop @t);
    }
    
    my $model_class_file = File::Spec->catfile($project_path{src},join('/',map{ucfirst} split('::',$class_name)).'.php');
    my $model_php_class_name =  join('_',map{ucfirst} split('::',$class_name));
    
    unless (-e $model_class_file) {
        mkpath(dirname($model_class_file));
        open FH,">$model_class_file" or fatal("Can't create file:$model_class_file");
        print FH <<"EOF";
<?php
/**
 * Model $model_name
 * 
 */
class ${model_php_class_name} extends DoggyX_Model_Lite \{
    protected \$collection = '$model_name';
    
    protected \$schema = array(
    );
    protected \$joins = array(
    );
    protected \$required_fields = array();
    protected \$ini_fields = array();

    // protected \$auto_update_timestamp = true;
    // protected \$created_timestamp_fields = array('created_on');
    // protected \$updated_timestamp_fields = array('updated_on');

    protected function extra_extend_model_row(&\$row) {
    }
    
    //~ some event handles
    protected function before_save(&\$data) {
    }
    protected function after_save() {
    }
    protected function validate() {
        return true;
    }
    
    
}
EOF
        print FH "?>";
        close(FH);
        print GREEN,'ok.skeleton class generated into ',$model_class_file,"\n",RESET;
    }
    else {
        print "model php skeleton exists,skip.\n";
    }
}


sub mk_action {
    my ($class_name) = shift;
    unless ($class_name) {
        help_message("action class_name is NULL");
    }
    my $action_class_file = File::Spec->catfile($project_path{src},join('/',map{ucfirst} split('::',$class_name)).'.php');
    my $ation_php_class_name =  join('_',map{ucfirst} split('::',$class_name));
    unless (-e $action_class_file) {
        mkpath(dirname($action_class_file));
        open FH,">$action_class_file" or fatal("Can't create file:$action_class_file");
        print FH <<"EOF";
<?php
/**
 * Action: $ation_php_class_name
 *
 */
class ${ation_php_class_name} extends Doggy_Dispatcher_Action_Lite \{
    public \$stash = array();
}
EOF
        print FH "?>";
        close(FH);
        print GREEN,'ok.skeleton action class generated into ',$action_class_file,"\n",RESET;
    }
    else {
        print "action $class_name exists, skip\n";
    }
}

sub mk_model_ar {
    init;
    my($table,$class_namespace) = @_;
    unless ($table or $class_namespace) {
        help_message("model_table_name or model_namespace is NULL");
    }
    $ENV{'DOGGY_APP_ROOT'} = $project_path{dev_root};
    my $include_path = "-d include_path=$project_path{php_class_path}";
    my $helper = File::Spec->catfile($doggy_path{script_root},$doggy_helper{MK_MODEL_AR});
    my $result = `php $include_path $helper $table`;
#    print $result;
    if ($?==0) {
        print "ok.\n";
    }
    else {
        print "failed.\n";
    }
    
    my $model_dir = $class_namespace;
    $model_dir =~ tr|_|/|;
    my $model_class_base = join('',map{ucfirst} split('_',$table));
    my $model_table_class = $class_namespace.'_Table_'.$model_class_base;
    my $model_full_class = $class_namespace.'_'.$model_class_base;
    
    my $model_table_file = File::Spec->catfile($project_path{src},$model_dir,'Table',$model_class_base.'.php');
    my $model_model_file = File::Spec->catfile($project_path{src},$model_dir,$model_class_base.'.php');
    print $model_table_file,",",$model_model_file,"\n";
    
    unless (-e File::Spec->catdir($project_path{src},$model_dir)) {
        mkpath(File::Spec->catdir($project_path{src},$model_dir));
    }
    unless (-e File::Spec->catdir($project_path{src},$model_dir,'Table')) {
        mkpath(File::Spec->catdir($project_path{src},$model_dir,'Table'));
    }
    open FH,">$model_table_file" or fatal("Can't create file:$model_table_file");
    my $stamp = localtime();
    print FH <<"EOF";
<?php
//this is auto generated file,don't modifiy it.
//generate on:$stamp
class ${model_table_class} extends Doggy_ActiveRecord_Base \{
    protected \$_fields = $result\;
}
?>
EOF
    print GREEN,"generate table meta class into:$model_table_file \n",RESET;
    close FH;
    if (-e $model_model_file) {
        print "warning:$model_model_file exists,skip.\n";
        return;
    }
    open FH,">$model_model_file" or fatal("Can't create file:$model_model_file");
    print FH <<"EOF";
<?php
class ${model_full_class} extends ${model_table_class} \{
    
\}
?>    
EOF
    close FH;
    print GREEN,"generate base class into: $model_model_file\n",RESET;
}

sub mk_test {
    my $t_path = shift;
    help_message('test file is null!') unless $t_path;
    # remove leading 't/'
    if (substr($t_path,0,2) eq 't/') {
        $t_path = substr($t_path,2);
    }
    
    
    $t_path = File::Spec->rel2abs($t_path,$project_path{test});
    my $t_dir = dirname($t_path);
    unless (-e $t_dir) {
        mkpath($t_dir) or fatal("Can't build test dir:$t_dir");
    }
    if (-e $t_path) {
        print "$t_path exists,skip..\n";
        exit(0);
    }
    
    open F,">$t_path";
    
    print F <<'EOF';
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
EOF
    print F "\n?>";
    close(F);
    print "success generate $t_path\n";
}


sub mk_project {
    my($project_name) = shift;
    unless ($project_name) {
        fatal('Project is NULL!');
    }
    my $project_id = join('-',map{lc} split('::',$project_name));
    my $project_root_dir = join('-',map{ucfirst} split('::',$project_name));
    my $project_namespace = join('::',map{ucfirst} split('::',$project_name));
    
    print YELLOW,"*** Project Information ***\n";
    print " App Id: $project_id\n";
    print " App Namespace: $project_namespace\n";
    print "--------------------------------\n\n",RESET;
    print "generate project directory skeleton...\n";
    
    mkdir $project_root_dir;
    mkpath(File::Spec->catdir($project_root_dir,'config'));
    mkpath(File::Spec->catdir($project_root_dir,'model_meta'));
    mkpath(File::Spec->catdir($project_root_dir,'t'));
    mkpath(File::Spec->catdir($project_root_dir,'deploy'));
    mkpath(File::Spec->catdir($project_root_dir,'compiled'));
    mkpath(File::Spec->catdir($project_root_dir,'vendor'));
    mkpath(File::Spec->catdir($project_root_dir,'data','templates'));
    mkpath(File::Spec->catdir($project_root_dir,'data','web'));
    mkpath(File::Spec->catdir($project_root_dir,'docs'));
    mkpath(File::Spec->catdir($project_root_dir,'src',split('::',$project_namespace),'Action'));
    
    mkpath(File::Spec->catdir($project_root_dir,'src',split('::',$project_namespace),'Model'));
    
    print "generate file skeleton...\n";
    
    _touch(File::Spec->catfile($project_root_dir,'TODO'));
    _touch(File::Spec->catfile($project_root_dir,'compiled','app.class.list'));
    copy(File::Spec->catfile($doggy_path{script_root},'Makefile'),File::Spec->catfile($project_root_dir,'Makefile')) or fatal("Can't copy makefile\n");
    
    print "generate app settings...\n";
    
    _create_app_yml(File::Spec->catfile($project_root_dir,'config','app.yml'),$project_id,$project_namespace);

    my $module_index_action_file = File::Spec->catfile($project_root_dir,'src',split('::',$project_namespace),'Action','Root.php');
    my $module_index_action_class = $project_namespace;
    $module_index_action_class =~ s|::|_|g;
    $module_index_action_class .="_Action_Root";
    
    print "generate app root module action...\n";
    my $action_content = "<?php\nclass $module_index_action_class extends Doggy_Dispatcher_Action_Lite {\n\n}\n?>";
    file_put_contents($action_content,$module_index_action_file);
    
    print "generate deploy example file...\n";
    _touch_deploy_yml(File::Spec->catfile($project_root_dir,'deploy','dev.yml.example'),'dev','debug');
    _touch_deploy_yml(File::Spec->catfile($project_root_dir,'deploy','test.yml.example'),'test','debug');
    _touch_deploy_yml(File::Spec->catfile($project_root_dir,'deploy','prod.yml.example'),'prod','info');
    
    print GREEN,"project:$project_name has been created.\n",RESET;
    print "*** Please modify app setting rightnow.****\n";
}

sub _create_app_yml {
    my $file = shift;
    my $app_id = lc(shift);
    my $app_namespace = shift;
    my $root_module_id;
    
    {
        my @t = split('::',$app_namespace);
        $root_module_id = lc(pop @t);
    }
    
    
    $app_namespace =~s|::|_|g;
    
    my $root_module_action = $app_namespace.'_Action_Root';
    my $root_module_namespace = $app_namespace;
    
    open FH,">$file" or fatal("Can't create file:$file");
    print FH <<"EOF";
---
app.id: '$app_id'
app.modules.$root_module_id: 
  state: 'on'
  namespace: '$root_module_namespace'
  index_action: 'Root'
app.modules.boot: '$root_module_id'
app.dispatcher.routes:
EOF
}


sub _touch_deploy_yml {
    my $file = shift;
    my $mode = shift;
    my $debug_level = shift;
    open FH,">$file" or fatal("Can't create file:$file");
    print FH <<"EOF";
---
app.db.default: 'mysql://root:\@localhost/test?charset=utf8'
#if in production mode,uncomment and change it to 'prod'
#app.mode: 'dev'
EOF
    close FH;
}
sub file_put_contents {
    my ($content,$path) = @_;
    open FH,">$path" or fatal("Can't create file:$path");
    print FH $content;
    close FH;
}

sub mk_module{
    my $module_name = shift;
    init;
    unless ($module_name) {
        fatal("You should specific a module name,like: App::Module \n");
    }
    
    my $module_dir = join('/',map{ucfirst} split('::',$module_name));
    if (-e File::Spec->catdir($project_path{src},$module_dir)) {
        fatal("Module:$module_name exists");
    }
    mkpath(File::Spec->catdir($project_path{src},$module_dir,'Action'));
    mkpath(File::Spec->catdir($project_path{src},$module_dir,'Model'));
    my $module_id = $module_name;
    {
       my @t = split('::',$module_name);
       $module_id = lc(pop @t);
    }
    
    my $file = File::Spec->catfile($project_path{root},'config','app.yml');
    my $module_namespace = join('_',map{ucfirst} split('::',$module_name));
    open FH,">>$file" or fatal("Can't open file:$file");
    print FH "#generated by doggy mk-module \n";
    print FH "app.modules.$module_id:\n";
    print FH "  state: 'on'\n";
    print FH "  index_action: 'Root'\n";
    print FH "  namespace: '$module_namespace'\n";
    print FH "#-----------end $module_id \n";
    close FH;
    print GREEN,"ok, $module_name created into  src/${module_dir}.\n",RESET;
    
}
sub test_all {
    my @args = @_;
    # my $include_path = "DOGGY_TEST_CLASS_PATH=\"$project_path{php_class_path}\"";
    # my $cmd = " $include_path prove  @args";
    # system(("/usr/bin/env $include_path /opt/local/bin/prove @args"));
    $ENV{'DOGGY_TEST_CLASS_PATH'} = $project_path{php_class_path};
    $ENV{'DOGGY_APP_ROOT'} = $project_path{dev_root};
    
    _build_test_rc();
    
    use App::Prove;
    my $app = App::Prove->new;
    $app->process_args(@args);
    exit( $app->run ? 0 : 1 );
}

sub _build_test_rc {
    my $test_rc = File::Spec->catfile($project_path{dev_root},'var','test.rc');
    my $test_settings = File::Spec->catfile($project_path{root},'deploy','test.yml');
    _merge_runtime_config($test_rc, $test_settings);
}

sub test {
    my $test_path = shift;
    my $include_path = "-d include_path=$project_path{php_class_path}";
    
    fatal("test file is NULL.") unless $test_path;
    
    # fix minor
    if ( !-e $test_path && (substr($test_path,0,1) ne 't') ) {
        $test_path  = 't/'.$test_path;
    }
    
    fatal("test file not found.") unless -e $test_path;
    $ENV{'DOGGY_APP_ROOT'} = $project_path{dev_root};
    _build_test_rc();
    system(('php',$include_path,$test_path));
}

sub run {
    my $php_file = shift;
    my $include_path = "-d include_path=$project_path{php_class_path}";
    $ENV{'DOGGY_APP_ROOT'} = $project_path{dev_root};
    system(('php',$include_path,$php_file));
}

sub server {
    init;
    my $bind_addr = shift;
    $bind_addr = 'localhost:8088' unless $bind_addr;
    my $php_fpm_addr = shift;
    $php_fpm_addr = '127.0.0.1:9000' unless $php_fpm_addr;
    my $webroot = File::Spec->catdir($project_path{dev_root}, 'web');
    my $varroot = File::Spec->catdir($project_path{dev_root}, 'var');
    my $prefix_dir = tempdir(DIR =>$varroot, CLEANUP => 1);
    my $logs_dir = File::Spec->catfile($prefix_dir, "logs");
    mkdir $logs_dir or die "failed to mkdir $logs_dir: $!";
    my $conf_dir = File::Spec->catfile($prefix_dir, "conf");
    mkdir $conf_dir or die "failed to mkdir $conf_dir: $!";
    my $conf_file = File::Spec->catfile($conf_dir, "nginx.conf");
    open my $out, ">$conf_file"
        or die "Cannot open $conf_file for writing: $!\n";

    my $app_nginx_conf = File::Spec->catfile($project_path{root}, 'deploy', 'nginx.doggy.conf');
    my $extra_conf_content = '';
    if (-e $app_nginx_conf) {
        $extra_conf_content = _read_file($app_nginx_conf);
    }
    print $out <<_EOC_;
    daemon off;
    master_process off;
    worker_processes 1;
    pid logs/nginx.pid;
    error_log /dev/stderr warn;
    #error_log /dev/stderr debug;
    events {
        worker_connections 64;
    }
    http {
        access_log /dev/stderr;
        error_log /dev/stderr;
        server {
            listen $bind_addr;
            server_name default;
            index app;
            client_max_body_size 60m;
            root $webroot;
            location /app {
                fastcgi_split_path_info ^(/app)(.*)\$;
                fastcgi_pass  $php_fpm_addr;
                fastcgi_pass_request_body off;
                client_body_in_file_only clean;
                fastcgi_param  GATEWAY_INTERFACE  CGI/1.1;
                fastcgi_param  SERVER_SOFTWARE    nginx;
                fastcgi_param  QUERY_STRING       \$query_string;
                fastcgi_param  REQUEST_METHOD     \$request_method;
                fastcgi_param  CONTENT_TYPE       \$content_type;
                fastcgi_param  CONTENT_LENGTH     \$content_length;
                fastcgi_param  SCRIPT_FILENAME    \$document_root\$fastcgi_script_name;
                fastcgi_param  SCRIPT_NAME        \$fastcgi_script_name;
                fastcgi_param  PATH_INFO          \$fastcgi_path_info;
                fastcgi_param  REQUEST_URI        \$request_uri;
                fastcgi_param  DOCUMENT_URI       \$document_uri;
                fastcgi_param  DOCUMENT_ROOT      \$document_root;
                fastcgi_param  SERVER_PROTOCOL    \$server_protocol;
                fastcgi_param  REMOTE_ADDR        \$remote_addr;
                fastcgi_param  REMOTE_PORT        \$remote_port;
                fastcgi_param  SERVER_ADDR        \$server_addr;
                fastcgi_param  SERVER_PORT        \$server_port;
                fastcgi_param  SERVER_NAME        \$server_name;
                fastcgi_param  REQUEST_BODY_FILE  \$request_body_file;
            }
        	location /__file_result__/ {
        	    internal;
        	    alias /;
        	}
            $extra_conf_content
        }
    }
_EOC_
    close $out;
    my $nginx_path = 'nginx';
    my $cmd = "$nginx_path -p $prefix_dir/ -c conf/nginx.conf";
    $SIG{INT} = \&sigint;
    my $pid = fork();
    if (!defined $pid) {
        die "fork() failed: $!\n";
    }
    if ($pid == 0) {  # child process
        #warn "exec $cmd...";
        exec $cmd or die "Failed to run command \"$cmd\": $!\n";
    } else {
        $child_pid = $pid;
        print "========================================================\n";
        print GREEN, "Nginx server running on PID:$pid, Runtime DIR: $prefix_dir\n", RESET;
        print "\n\nWeb address: ", BOLD, ON_MAGENTA, "http://$bind_addr \n\n", RESET;
        print 'PHP-FPM backend: ', BOLD, ON_CYAN, "$php_fpm_addr \n\n", RESET;
        print "========================================================\n";
        waitpid($child_pid, 0);
        my $rc = 0;
        if (defined $?) {
            $rc = ($? >> 8);
        }
        exit($rc);
    }
}

sub show_version{
    print "Doggy framework,","version:$VERSION, ";
    print "Copyright 2001-2010 by Night Sailer\n";
}
sub help_message{
    my ($error_message) = shift;
    if ($error_message) {
        print RED," ERROR $error_message\n",RESET;
    }
    
    print << "_EOF_";
usage:doggy  COMMANDS  [ARGS]
    doggy init
    doggy build
    doggy mk_model <model_table_name>
    doggy mk_test <php_class_name>
    doggy deploy
    ....

The most commonly used doggy commands are:
    init
        Prepare project dev enviroment.
        
    dev
        Quick deploy project use dev enviroment.
        
    clean
        Clean project build data.
        
    deploy [deploy_schema_name | prod]
        Install and deploy application into target directory.
        if omit deploy_schema_name, assume use deploy/prod.yml
        
    mk-project <project name> [target directory]
        Generate a doggy project skeleton.
        
    mk-test  test_file_path
        Generate a test skeleton. test_file_path is relative path under t/
    
    mk-model App::Model::Class_Name [model_name]
        Generate model class skeleton, also generate/update model db table meta into config/meta/model_class.meta,
        this meta data will merge to Doggy_Config when deploy.

    mk-model-x App::Model::Class_Name [collection_name]
        ***NEW!! Generate a DoggyX::Model::Mongo::Base model skeleton. ***
    
    mk-module App::Module
        Generate a module skeleton. Add module's related setting to app.yml.
    
    mk-action App::Action::ClassName
        Genenrate a action ,class name is App_Action_ClassName
        
    compile-doggy [altername_list_name]
        Merge Doggy class into singel file. The class list default is doggy_install_dir/compiled/doggy.class.list.
        If specific [altername_list_name], the class list will be doggy_install_dir/compiled/doggy.<altername_list_name>.list

    compile-app
        Merge application classes into single huge and big file.This file will be included runtime.
        The class list file is  <project_root_dir>/compiled/app.class.list.
        
    test  t/xxx.t
        run single testcase.
        
    test-all [-r] [t/sub_dir_name]
        use App::Prove to all run tests. More information see:man prove
    
    run  php_file_path
        Run php script in current project dev enviroment.

    server [addr:port, DEFAULT:127.0.0.1:8088] [php-fpm-backend-addr:port, DEFAULT: 127.0.0.1:9000]
        Run openresty/nginx frontend, bind to addr:port(DEFAULT: 127.0.0.1:8088).
        You can specific alternated php-fpm backend address, default is: 127.0.0.1:9000 .
        Examples:
            doggy server localhost:8008
            doggy server 127.0.0.1:8009 127.0.0.1:9001
        
    -h , --help
        Show this usage message.
    -v , --version 
        Print Doggy framework version.
    
_EOF_
    exit 1;
}
1;

=head1 NAME

Doggy build system - Doggy build/deploy script system.

=head1 SYNOPSIS

use Doggy;
#setup current doggy version
Doggy::VERSION = 'v1.3.x'; 
#load doggy from given dir.
Doggy::load_doggy($doggy_installed_root);
#dispatch task from command line.
Doggy::do_task(@ARGV);