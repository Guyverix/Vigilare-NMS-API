<?php
use FreeDSx\Snmp\SnmpClient;
require __DIR__ . '/../vendor/autoload.php';


/*
Command args:

-h  host / ip
-c  community
-v  version
-o  oid
-t  table (walk)

php ./pollerTest.php -h 192.168.15.58 -v 2 -c public -o 1.3.6.1.2.1.92.1.3.1.1.8.7.100.101.102.97.117.108.116.1 -t
Array
(
    [h] => 192.168.15.58
    [v] => 2
    [c] => public
    [o] => 1.3.6.1.2.1.92.1.3.1.1.8.7.100.101.102.97.117.108.116.1
    [t] => true
)
*/


/* Set our optargs here */
$options = getopt("h:c:v:o:t");
if ( isset($options['h'])) {
  $host=$options['h'];
}
if ( isset ($options['v'])) {
  $version=$options['v'];
}
if ( isset ($options['c'])) {
  $comm=$options['c'];
}
if ( isset ($options['o'])) {
  $searchOid=$options['o'];
}

if ( ! isset($host,$version,$comm,$searchOid) ) {
  echo "Command values must be set to use this script\n";
  exit(2);
}

if (array_key_exists("t",$options)) {
  $tableWalk='true';
}
else {
  $tableWalk='false';
}

//print_r($options);

$snmp = new SnmpClient([
    'host' => "$host",
    'version' => intval("$version"),
    'community' => "$comm",
]);


if ( $tableWalk == 'false' ) {
//  echo $snmp->getValue("$searchOid").PHP_EOL;
  $oid = $snmp->getOid("$searchOid");
//  echo sprintf("%s == %s", $oid->getOid(), (string) $oid->getValue()).PHP_EOL;
  echo sprintf("%s == %s", $oid->getOid(), (string) $oid->getValue()).PHP_EOL;
  echo "bin2hex ". bin2hex($oid->getValue()) ."\n";
//print_r($oid);
//  print_r($snmp);
}
else {
  # Using the SnmpClient, get the helper class for an SNMP walk...
  $walk = $snmp->walk($searchOid);
  # Keep the walk going until there are no more OIDs left
  while($walk->hasOids()) {
    try {
      # Get the next OID in the walk
      $oid = $walk->next();
      echo sprintf("%s == %s", $oid->getOid(), (string) $oid->getValue()).PHP_EOL;
    }
    catch (\Exception $e) {
      # If we had an issue, display it here (network timeout, etc)
      echo "Unable to retrieve OID. ".$e->getMessage().PHP_EOL;
    }
  }
  print_r($walk) ;
}

?>
