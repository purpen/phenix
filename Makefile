current_root=`pwd`
init:
	doggy init
		
test:	init
	test -e $(current_root)/deploy/test.yml || (echo "you must create test.yml under deploy" && exit 2)
	doggy test-all -r
	
js:
	cat data/web/js/jquery.bar.js \
	data/web/js/jquery.taconite.js \
	data/web/js/jquery.validate.js \
	data/web/js/jquery.form.js \
	data/web/js/select2.js \
	data/web/js/jquery.imgareaselect.pack.js \
	data/web/js/jquery.fineuploader-3.5.0.min.js \
	data/web/js/scrolltopcontrol.js \
	> data/web/js/jquery_tmp.js 
	java -jar support/yuicompressor-2.4.2.jar -v --charset utf-8 --type js \
	data/web/js/jquery_tmp.js  \
	-o data/web/js/bundle.jquery.js
	rm -f data/web/js/jquery_tmp.js
	java -jar support/yuicompressor-2.4.2.jar -v --charset utf-8 --type js \
	data/web/js/sher.js \
	-o data/web/js/bundle.sher.js

css:
	cat data/web/css/jquery.vegas.css \
	data/web/css/select2.css \
	data/web/css/imgareaselect-default.css \
	data/web/css/fineuploader-3.5.0.css \
	data/web/css/base.css \
	> data/web/css/sher_tmp.css
	java -jar support/yuicompressor-2.4.2.jar -v --charset utf-8 --type css \
	data/web/css/sher_tmp.css \
	-o data/web/css/bundle.common.css
	rm -f data/web/css/sher_tmp.css

web: js css