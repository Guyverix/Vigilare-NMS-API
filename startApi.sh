#!/bin/bash

# This is going to have to get made into a systemd control
# right now it is fine for debug and testing, but not prod ready

# All use php-cli ini values.  Keep in mind different than php web ini values

# Start debug API system Slim4
#nohup php -S 0.0.0.0:8002 -t public 2>&1 >> ./logs/app.log &

# Start housekeeping watchdog
pushd daemon/housekeepingPoller/
  nohup ./housekeepingPoller.php -i 60 -s start 2>&1 >/dev/null &
popd

# Start general daemon controls
pushd daemon/Poller/
#  nohup ./genericPoller.php -i 300 -t alive -s start 2>&1 >/dev/null &
  nohup ./genericPoller.php -i 300 -t nrpe -s start 2>&1 >/dev/null &
  sleep 1
  nohup ./genericPoller.php -i 300 -t snmp -s start 2>&1 >/dev/null &
  sleep 1
  nohup ./genericPoller.php -i 60 -t alive -s start 2>&1 >/dev/null &
  sleep 1
  nohup ./genericPoller.php -i 300 -t shell -s start 2>&1 >/dev/null &
popd

