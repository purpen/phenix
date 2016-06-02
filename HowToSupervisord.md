# Setup

1. install pip

    easy_install pip

2. Install virtualenv && virtualenvwrapper

    $ pip install virtualenv
    $ pip install virtualenvwrapper

create python envs dir, then setup environment vars and alias

    $ mkdir -p /opt/python/envs
    $ cat /etc/profile.d/pythonbin.sh
    export WORKON_HOME=/opt/python/envs
    source /usr/bin/virtualenvwrapper.sh
    alias v='workon'
    alias v.deactivate='deactivate'
    alias v.mk='mkvirtualenv --no-site-packages'
    alias v.mk_withsitepackages='mkvirtualenv'
    alias v.rm='rmvirtualenv'
    alias v.switch='workon'
    alias v.add2virtualenv='add2virtualenv'
    alias v.cdsitepackages='cdsitepackages'
    alias v.cd='cdvirtualenv'
    alias v.lssitepackages='lssitepackages'

apply the script

    $ source /etc/profile.d/pythonbin.sh

3. Create taihuoniao environment

    $ v.mk taihuoniao
    $ v.switch taihuoniao
    $ pip install -r requirements.txt

    quit env: deactivate


# Running supervisord daemon

First only:

    $ cp supervisord_example.conf supervisord.conf

    $ supervisord


# Start/stop workers

    $ supervisorctl start/stop <worker_name>

Reload all workers

    $ supervisorctl reload



一、添加好配置文件后

二、更新新的配置到supervisord
supervisorctl update

三、重新启动配置中的所有程序
supervisorctl reload

四、启动某个进程(program_name=你配置中写的程序名称)
supervisorctl start program_name

五、查看正在守候的进程
supervisorctl

六、停止某一进程 (program_name=你配置中写的程序名称)
pervisorctl stop program_name

七、重启某一进程 (program_name=你配置中写的程序名称)
supervisorctl restart program_name

八、停止全部进程
supervisorctl stop all

注意：显示用stop停止掉的进程，用reload或者update都不会自动重启。
