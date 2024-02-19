<?php

require __DIR__ . '/../vendor/autoload.php';

use Acamposm\SnmpPoller\SnmpPoller;
use Acamposm\SnmpPoller\Pollers\IfTablePoller;
use SNMP;


$session = new SNMP(SNMP::VERSION_2C, '192.168.15.58', 'public');

$poller = new SnmpPoller();

$poller->setSnmpSession($session)->addPoller(IfTablePoller::class)->run();
?>
