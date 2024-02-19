<?php
use FreeDSx\Snmp\SnmpClient;
require __DIR__ . '/../vendor/autoload.php';

$snmp = new SnmpClient([
    'host' => '192.168.15.58',
    'version' => 2,
    'community' => 'public',
]);

# Get a specific OID value as a string...
echo $snmp->getValue('1.3.6.1.2.1').PHP_EOL;

# Get a specific OID as an object...
$oid = $snmp->getOid('1.3.6.1.2.1');
var_dump($oid);

echo sprintf("%s == %s", $oid->getOid(), (string) $oid->getValue()).PHP_EOL;

# Get multiple OIDs and iterate through them as needed...
$oids = $snmp->get('1.3.6.1.2.1.1.1', '1.3.6.1.2.1.1.3', '1.3.6.1.2.1.1.5');
 
foreach($oids as $oid) {
    echo sprintf("%s == %s", $oid->getOid(), (string) $oid->getValue()).PHP_EOL;
}

# Using the SnmpClient, get the helper class for an SNMP walk...
$walk = $snmp->walk();

# Keep the walk going until there are no more OIDs left
while($walk->hasOids()) {
    try {
        # Get the next OID in the walk
        $oid = $walk->next();
        echo sprintf("%s = %s", $oid->getOid(), $oid->getValue()).PHP_EOL;
    } catch (\Exception $e) {
        # If we had an issue, display it here (network timeout, etc)
        echo "Unable to retrieve OID. ".$e->getMessage().PHP_EOL;
    }
}

echo sprintf("Walked a total of %s OIDs.", $walk->count()).PHP_EOL; 
?>
