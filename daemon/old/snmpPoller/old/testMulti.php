<?php


use Acamposm\SnmpPoller\SnmpPoller;
use Acamposm\SnmpPoller\Pollers\IfTablePoller;
//use Acamposm\SnmpPoller\Pollers\IfExtendedTablePoller;
//use Acamposm\SnmpPoller\Pollers\EntPhysicalTablePoller;
//use Acamposm\SnmpPoller\Pollers\LldpRemoteTablePoller;
use SNMP;

require __DIR__ . '/../vendor/autoload.php';

//$session = new SNMP('SNMP::VERSION_2C', '192.168.15.58', 'public');
//$session = new SNMP(SNMP::VERSION_1, '192.168.15.58', 'public');
$session = (SNMP::VERSION_1, '192.168.15.58', 'public');
//$session = new SNMP('2c', '192.168.15.58', 'public');

$poller = new SnmpPoller();

$poller->setSnmpSession($session)->addPoller(IfTablePoller::class)->run();

/*
$pollerClasses = [
   IfTablePoller::class,
   IfExtendedTablePoller::class,
   EntPhysicalTablePoller::class,
   LldpRemoteTablePoller::class,
];

//print_r($pollerClasses);
$poller->setSnmpSession($session)
       ->addPollers($pollerClasses)
       ->run();
*/
?>
