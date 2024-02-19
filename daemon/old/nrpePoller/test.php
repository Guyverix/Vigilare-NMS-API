<?php


$checkCommand2='/usr/lib/nagios/plugins/check_ping -H $hostname -w 1,20% -c 2,30% -4';
$hostname='foo.bar.baz';
$address='1.2.3.4';
$checkName="checkName";
$nrpePath="blah";



      echo "TESTING COMMAND " . $checkCommand2 . "\n";
      // echo "TESTING ATTRIBUTES " . print_r($cmdAttrib) . "\n";
      // echo "TESTING ATTRIBUTES STRING SNMPSETUP" . $cmdAttrib['SNMP']['snmpSetup'] . "\n";
      $checkCommand = eval( 'return "' . $checkCommand2 . '";') ;
      echo "DEBUG: checkName: " . $checkName . " checkCommand: " . $checkCommand . " hostname: " . $hostname . " IP " . $address . "\n";
      echo "Running nrpe command: " . $nrpePath . " -H " . $hostname . " -c " . $checkCommand . "\n";

?>

