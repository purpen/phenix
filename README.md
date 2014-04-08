TaiHuoNiao程序源码


程序部署要求：
php 5.3.0+ 
  依赖库Datetime
  
  
#Q: Access denied 问题
修改php-fpm.conf中的 security.limit_extensions 参数
security.limit_extensions = app .php .php3 .php4 .php5 

#Q: ajax form提交问题
event.preventDefault();

#Q:Doggy_Log_Helper::error('Failed to create_passport:'.$e->getMessage());

#Q:ID起点从10000起。

#Q:resque任务处理。