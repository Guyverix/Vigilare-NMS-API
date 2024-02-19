<?php
use FreeDSx\Snmp\SnmpClient;
require __DIR__ . '/../vendor/autoload.php';

$snmp = new SnmpClient([
    'host' => '192.168.15.58',
    'version' => 2,
    'community' => 'public',
]);


# Using the SnmpClient, get the helper class for an SNMP walk...
//$walk = $snmp->walk();
//$walk = $snmp->walk('1.3.6.1.2.1.2.2.1.1');
$walk = $snmp->walk('1.3.6.1.2.1.55.1.5.1.8.2');
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


print_r($walk) ;
?>
