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
