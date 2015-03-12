此目录用于放置部署方案.
一个方案对应于1个部署文件: 
dev.yml - 用于开发环境的部署配置
default.yml - 用于默认正式Production环境部署
another_prod.yml  - 其他正式环境部署

你可以使用doggy脚本来实施对应方案的部署工作
$>doggy deploy 