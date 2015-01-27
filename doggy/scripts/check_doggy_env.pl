#!/usr/bin/env perl
use Term::ANSIColor qw(:constants);

print "check YAML...";
eval 'use YAML;';
if ($@) {
    print RED,"failed. please install:",MAGENTA,"cpan -i YAML\n",RESET;
    exit(1);
}
else {
    print GREEN,"ok.\n",RESET;
}

print "check Test-Harness ...";
eval 'use App::Prove;';
if ($@) {
    print RED,"failed. please install,",MAGENTA,"cpan -i Test::Harness\n",RESET;
    exit(1);
}
else {
    print GREEN,"ok.\n",RESET;
}

print 'check PHP ...';
my $php_bin = `php-config  --php-binary`;
chomp $php_bin;

my $php_version = `php-config --version`;
chomp $php_version;

if ($php_bin) {
    print GREEN,"ok,found at ${php_bin}(version:$php_version)\n",RESET;
}
else {
    print RED,"failed.Where is your PHP?\n",RESET;
    exit(1);
}

my @base_extensions = qw(json memcached xdebug imagick);
my @depricated_extensions = qw(magickwand memcache);
my @opt_extensions = qw(mongo gearman syck xcache );

print "check Doggy required php extension:\n";
my $missing = check_pecl_ext(@base_extensions);
if ($missing) {
    print RED,"ERROR: one or more required PECL extension missing,for Doggy framework works,please install them!.\n",RESET;
}
else {
    print GREEN,"ok.\n",RESET;
}
print "check deprecated php extension:\n";
$missing = check_pecl_ext(@depricated_extensions);
if ($missing != @depricated_extensions) {
    print CYAN,"Warn:some of deprecated extension found,recommend you uninstall them to avoid conflict with other extensions.\n",RESET;
}
else {
    print GREEN,"ok.\n",RESET;
}

print "check optional php extension:\n";
$missing = check_pecl_ext(@opt_extensions);
if ($missing) {
    print CYAN,"Warn:some of optional extension missing,for best performance,recommend you install them.\n",RESET;
}
else {
    print GREEN,"ok.\n";
}

exit(0);

sub check_pecl_ext {
    my (@check_list) = @_;
    my $missing = 0;
    foreach $ext (@check_list) {
        print "  $ext ...";
        my $check_php_code ="'if(extension_loaded(\"$ext\")) exit(0); else exit(1);'";
        `php -r $check_php_code`;
        if (!$?) {
            print " found.\n";
        }
        else {
            print " missing.\n";
            $missing++;
        }
    }
    return $missing;
}
1;