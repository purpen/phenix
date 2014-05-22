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

### Composer 安装
See http://composer.golaravel.com/

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


###移动网页优化
一个常用的针对移动网页优化过的页面的 viewport meta 标签大致如下：

<meta name=”viewport” content=”width=device-width, initial-scale=1, maximum-scale=1″>

width：控制 viewport 的大小，可以指定的一个值，如果 600，或者特殊的值，如 device-width 为设备的宽度（单位为缩放为 100% 时的 CSS 的像素）。
height：和 width 相对应，指定高度。
initial-scale：初始缩放比例，也即是当页面第一次 load 的时候缩放比例。
maximum-scale：允许用户缩放到的最大比例。
minimum-scale：允许用户缩放到的最小比例。
user-scalable：用户是否可以手动缩放

 