current_root=`pwd`
init:
	doggy init
		
test:	init
	test -e $(current_root)/deploy/test.yml || (echo "you must create test.yml under deploy" && exit 2)
	doggy test-all -r