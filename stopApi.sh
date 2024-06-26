#!/bin/bash

# This is going to have to get made into a systemd control
# right now it is fine for debug and testing, but not prod ready

# All use php-cli ini values.  Keep in mind different than php web ini values

# stop debug API system Slim4
#nohup php -S 0.0.0.0:8002 -t public 2>&1 >> ./logs/app.log &

# stop housekeeping watchdog
# Converted to systemd as of 03-16-2024

#pushd daemon/housekeepingPoller/
#  ./housekeepingPoller.php -i 60 -s stop
#popd

service vigilare.housekeeping stop

# stop general daemon controls
pushd daemon/Poller/
  ./genericPoller.php -i 300 -t nrpe -s stop
  ./genericPoller.php -i 300 -t snmp -s stop
  ./genericPoller.php -i 60 -t alive -s stop
  ./genericPoller.php -i 300 -t shell -s stop
popd

