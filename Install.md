### 生产环境版本:
php: v5.3.28
mongodb: v2.6.4
nginx: v1.6.2
git
redis: v2.8.9
xun-search: v1.4.9


### PHP5.3.0+安装（for Mac）
依赖库Datetime

brew install php56 --with-fpm --with-mysql

brew install php56-gmagick php56-mcrypt php56-mongo php56-redis php56-xcache php56-yaml

brew install php56-uploadprogress php56-xdebug php56-oauth

#### 使用PHPSTORM和XDEBUG优化PHP程序
see http://h5b.net/phpstorm-xdebug-profiler-optimization-php/


XCache还不支持PHP 5.5.0，根据官方说法，XCache 3.1会支持PHP 5.5

### Questions
1、修改php-fpm.conf中的 security.limit_extensions 参数
   
   security.limit_extensions = app .php .php3 .php4 .php5 
   
2、
redis
### Composer 安装
See http://composer.golaravel.com/
$ curl -sS https://getcomposer.org/installer | php
composer install

PHP Composer软件包列表：
https://packagist.org/

PHP 开发者该知道的5个 Composer 小技巧:
http://segmentfault.com/a/1190000000355928


Composer使用json作为其配置文件的格式。

3.2 autoload
composer支持PSR-0,PSR-4,classmap及files包含以支持文件自动加载。PSR-4为推荐方式。

3.2.1 Files类型
格式："autoload":{"files":["path/to/1.php","path/to/2.php",...]}
支持将数组中的文件进行自动加载，文件的路径相对于项目的根目录。缺点是麻烦，需要将所有文件都写进配置。

3.2.2 classmap类型
格式："autoload":{"classmap": ["path/to/src1","path/to/src2",...]}
支持将数组中的路径下的文件进行自动加载。其很方便，但缺点是一旦增加了新文件，需要执行dump-autoload命令重新生成映射文件vendor/composer/autoload_classmap.php。

3.2.3 psr-0类型
格式："autoload":{"psr-0":{
                "name1\\space\\":["path/",...],
                "name2\\space\\":["path2/",...],
              }
     }
支持将命名空间映射到路径。命名空间结尾的\\不可省略。当执行install或update时，加载信息会写入vendor/composer/autoload_namespace.php文件。如果希望解析指定路径下的所有命名空间，则将命名空间置为空串即可。
需要注意的是对应name2\space\Foo类的类文件的路径为path2/name2/space/Foo.php

3.2.4 psr-4类型
格式："autoload":{"psr-4":{
                "name1\\space\\":["path/",...],
                "name2\\space\\":["path2/",...],
     	 }
     }
支持将命名空间映射到路径。命名空间结尾的\\不可省略。当执行install或update时，加载信息会写入vendor/composer/autoload_psr4.php文件。如果希望解析指定路径下的所有命名空间，则将命名空间置为空串即可。
需要注意的是对应name2\space\Foo类的类文件的路径为path2/space/Foo.php，name2不出现在路径中。

PSR-4和PSR-0最大的区别是对下划线（underscore)的定义不同。PSR-4中，在类名中使用下划线没有任何特殊含义。而PSR-0则规定类名中的下划线_会被转化成目录分隔符。

See http://segmentfault.com/a/1190000000380008

### Scws文档
See http://www.xunsearch.com/scws/docs.php


### jquery.easing插件
See http://gsgd.co.uk/sandbox/jquery/easing/


### sly轮换图
See http://darsa.in/sly

### 导入初始数据
mongorestore -d firebird -c areas ~/Project/phenix/install/china_city.bson
mongoimport -d firebird -c areas ~/Project/phenix/install/china_city.bson



###移动网页优化
一个常用的针对移动网页优化过的页面的 viewport meta 标签大致如下：

<meta name=”viewport” content=”width=device-width, initial-scale=1, maximum-scale=1″>

width：控制 viewport 的大小，可以指定的一个值，如果 600，或者特殊的值，如 device-width 为设备的宽度（单位为缩放为 100% 时的 CSS 的像素）。
height：和 width 相对应，指定高度。
initial-scale：初始缩放比例，也即是当页面第一次 load 的时候缩放比例。
maximum-scale：允许用户缩放到的最大比例。
minimum-scale：允许用户缩放到的最小比例。
user-scalable：用户是否可以手动缩放


### 部署修正
1、分类增加类组及二级分类

2、定时任务：
   相关目录var/log设置权限；
   执行文件，sbin/cron_service.sh,bin/cron_service_guard.sh,bin/cron_service.php设置执行权限；
   启动、关闭脚本，sbin/core_service.sh start, sbin/core_service.sh stop;

3、购物过程事务处理


function create_cookie(name, value, days, domain, path){
	var expires = '';
	if (days) {
		var d = new Date();
		d.setTime(d.getTime() + (days*24*60*60*1000));
		expires = '; expires=' + d.toGMTString();
	}
	domain = domain ? '; domain=' + domain : '';
	path = '; path=' + (path ? path : '/');
	document.cookie = name + '=' + value + expires + path + domain;
}

function read_cookie(name){
	var n = name + '=';
	var cookies = document.cookie.split(';');
	for (var i = 0; i < cookies.length; i++) {
		var c = cookies[i].replace(/^\s+/, '');
		if (c.indexOf(n) == 0) {
			return c.substring(n.length);
		}
	}
	return null;
}

function erase_cookie(name, domain, path){
	setCookie(name, '', -1, domain, path);
}


pip
pip也是一个包管理工具，它和setuptools类似，如果使用virtualenv，会自动安装一个pip。

# pip install PACKAGE           # 安装包
# pip -f URL install PACKAGE    # 从指定URL下载安装包
# pip -U install PACKAGE        # 升级包

virtualenv
virtualenv是一个Python环境配置和切换的工具，可以用它配置多个Python运行环境，和系统中的Python环境隔离，即所谓的沙盒。沙盒的好处包括：

解决库之间的版本依赖，比如同一系统上不同应用依赖同一个库的不同版本。
解决权限限制，比如你没有 root 权限。
尝试新的工具，而不用担心污染系统环境。
$ virtualenv py-for-web
这样就创建了一个名为py-for-web的Python虚拟环境，实际上就是将Python环境克隆了一份。然后可以用 source py-for-web/bin/activate 命令来更新终端配置，修改环境变量。接下来的操作就只对py-for-web环境产生影响了，可以使用 pip 命令在这里安装包，当然也可以直接安装。

$ source py-for-web/bin/activate    # 启用虚拟环境
$ deactivate                        # 退出虚拟环境
有个virtualenv-sh包，对virtualenv做了一些终端命令的增强。安装之后，在~/.bashrc中添加配置：

. /usr/local/bin/virtualenv-sh.bash
它提供的几个常用的命令如：

mkvirtualenv <env_name>     在$WORKON_HOME创建虚拟环境
rmvirtualenv <env_name>     删除虚拟环境
workon [<env_name>]         切换到虚拟环境
deactivate                  退出虚拟环境
lsvirtualenvs               列出全部的虚拟环境
cdvirtualenv [subdir]       进入虚拟环境的相应目录
$WORKON_HOME 的默认值为 ${HOME}/.virtualenvs 。

Supervisord管理
Supervisord安装完成后有两个可用的命令行supervisor和supervisorctl，命令使用解释如下：

supervisord，初始启动Supervisord，启动、管理配置中设置的进程。
supervisorctl stop programxxx，停止某一个进程(programxxx)，programxxx为[program:chatdemon]里配置的值，这个示例就是chatdemon。
supervisorctl start programxxx，启动某个进程
supervisorctl restart programxxx，重启某个进程
supervisorctl stop groupworker: ，重启所有属于名为groupworker这个分组的进程(start,restart同理)
supervisorctl stop all，停止全部进程，注：start、restart、stop都不会载入最新的配置文件。
supervisorctl reload，载入最新的配置文件，停止原有进程并按新的配置启动、管理所有进程。
supervisorctl update，根据最新的配置文件，启动新配置或有改动的进程，配置没有改动的进程不会受影响而重启。
注意：显示用stop停止掉的进程，用reload或者update都不会自动重启。


## 基于Linux Crontab 订时任务
1. 依赖于Composer: "jenner/crontab": "1.0.0"
2. 配置文件位于根目录: crontab_manager_example.php *复制此文件并改名 crontab_manager.php
3. 执行系统crontab命令：crontab -e 并追加一条配置 ** 注意路径
*/1 * * * *  cd /opt/project/phenix && /usr/bin/env php crontab_manager.php


 
