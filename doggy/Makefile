current_root=`pwd`
check:
	perl scripts/check_doggy_env.pl

init:
	bin/doggy init
		
test:	init
	test -e $(current_root)/deploy/test.yml || (echo "you must create test.yml under deploy" && exit 2)
	bin/doggy test-all -r

install:
	test -x 'bin/doggy' || chmod +x bin/doggy
	(test -d /opt/local/bin &&  rm -f /opt/local/bin/doggy && ln -s $(current_root)/bin/doggy /opt/local/bin/doggy)  || \
	(test -d /usr/local/bin &&  rm -f /usr/local/bin/doggy && ln -s $(current_root)/bin/doggy /usr/local/bin/doggy)
	@echo "ok, doggy symbol link install into => `which doggy`"

uninstall:
	(test -e /opt/local/bin/doggy  && rm -f /opt/local/bin/doggy) || \
	(test -e /usr/local/bin/doggy  && rm -f /usr/local/bin/doggy )