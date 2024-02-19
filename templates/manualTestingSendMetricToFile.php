<?php
/*
  This file is used to parse SNMP return values to make something useful in the database
  without additional processing.  In general think things that do not change often, but we
  want to have in the db.  then this is called with a slow iteration cycle.

  The template itself is going to generate all of our data that we are inserting into the
  database.  What it returns will be what is shoved up in there.  So templates will need
  to json_encode arrays to send to the database.  This specific page will choke on sending
  an array to the database, and will likely return an integer 1 if there is a mistake.

*/

// UTILITY FUNCTIONS
require_once __DIR__ . '/../src/Infrastructure/Shared/Functions/daemonFunctions.php';

class MetricParsingFile {
  public $hostname;
  public $dataToBeInserted;
  public $checkName;
  public $returnArrayValues;
  public $fileName;

  public function __construct() {}
  public function parseRaw($hostname, $dataToBeInserted, $checkName, $cycle = null, $type = null) {
    $fileName=__DIR__ . "/../file/" . $hostname . "/" . $checkName . ".txt";
    if (file_exists($fileName)) {
      return 0;
    }
    else {
      $pathParts = pathinfo($fileName);
      if ( ! file_exists($pathParts['dirname'])) {
        mkdir($pathParts['dirname'],0777, true);
      }

      if (touch($fileName)) {
        echo "Failed to create filename " . $fileName . "\n";
        return 1;
      }
      else {
        return 0;
      }
    }  // end else
  } // end function
} // end CLASS

function sendMetricToFile($hostname, $dataToBeInserted, $checkName, $cycle = null, $type = null) {
  if (! isset($metricData)) {
    // This should make an object to use for metrics
    $metricData=new MetricParsingFile();
  }

  // We should now have a basic object called $metricData created
  $metricData->parseRaw($hostname, $dataToBeInserted, $checkName);

  // We know we have a filename existing, now write data into it.
  $fileName=__DIR__ . "/../file/" . $hostname . "/" . $checkName . ".txt";
  file_put_contents($fileName, $dataToBeInserted);

  // Do NOT allow memory bleeds.  Unset stuff ASAP
  return 0;
}


/*
// Generic data
$hostname="test.iwillfearnoevil.com";
$checkName="randomCheckName";
$dataToBeInserted='{"iso.3.6.1.2.1.31.1.1.1.1.1":"lo","iso.3.6.1.2.1.31.1.1.1.1.2":"enp2s0f0","iso.3.6.1.2.1.31.1.1.1.1.3":"enp2s0f1","iso.3.6.1.2.1.31.1.1.1.2.1":"0","iso.3.6.1.2.1.31.1.1.1.2.2":"2737063","iso.3.6.1.2.1.31.1.1.1.2.3":"2737064","iso.3.6.1.2.1.31.1.1.1.3.1":"0","iso.3.6.1.2.1.31.1.1.1.3.2":"0","iso.3.6.1.2.1.31.1.1.1.3.3":"0","iso.3.6.1.2.1.31.1.1.1.4.1":"0","iso.3.6.1.2.1.31.1.1.1.4.2":"0","iso.3.6.1.2.1.31.1.1.1.4.3":"0","iso.3.6.1.2.1.31.1.1.1.5.1":"0","iso.3.6.1.2.1.31.1.1.1.5.2":"0","iso.3.6.1.2.1.31.1.1.1.5.3":"0","iso.3.6.1.2.1.31.1.1.1.6.1":"1391729","iso.3.6.1.2.1.31.1.1.1.6.2":"541515647212","iso.3.6.1.2.1.31.1.1.1.6.3":"6580280614379","iso.3.6.1.2.1.31.1.1.1.7.1":"15525","iso.3.6.1.2.1.31.1.1.1.7.2":"404437332","iso.3.6.1.2.1.31.1.1.1.7.3":"4713635082","iso.3.6.1.2.1.31.1.1.1.8.1":"0","iso.3.6.1.2.1.31.1.1.1.8.2":"2737063","iso.3.6.1.2.1.31.1.1.1.8.3":"2737064","iso.3.6.1.2.1.31.1.1.1.9.1":"0","iso.3.6.1.2.1.31.1.1.1.9.2":"0","iso.3.6.1.2.1.31.1.1.1.9.3":"0","iso.3.6.1.2.1.31.1.1.1.10.1":"1391729","iso.3.6.1.2.1.31.1.1.1.10.2":"42372311","iso.3.6.1.2.1.31.1.1.1.10.3":"698050599555","iso.3.6.1.2.1.31.1.1.1.11.1":"15525","iso.3.6.1.2.1.31.1.1.1.11.2":"246616","iso.3.6.1.2.1.31.1.1.1.11.3":"1329990006","iso.3.6.1.2.1.31.1.1.1.12.1":"0","iso.3.6.1.2.1.31.1.1.1.12.2":"0","iso.3.6.1.2.1.31.1.1.1.12.3":"0","iso.3.6.1.2.1.31.1.1.1.13.1":"0","iso.3.6.1.2.1.31.1.1.1.13.2":"0","iso.3.6.1.2.1.31.1.1.1.13.3":"0","iso.3.6.1.2.1.31.1.1.1.15.1":"10","iso.3.6.1.2.1.31.1.1.1.15.2":"1000","iso.3.6.1.2.1.31.1.1.1.15.3":"1000","iso.3.6.1.2.1.31.1.1.1.16.1":"2","iso.3.6.1.2.1.31.1.1.1.16.2":"2","iso.3.6.1.2.1.31.1.1.1.16.3":"2","iso.3.6.1.2.1.31.1.1.1.17.1":"2","iso.3.6.1.2.1.31.1.1.1.17.2":"1","iso.3.6.1.2.1.31.1.1.1.17.3":"1","iso.3.6.1.2.1.31.1.1.1.18.1":"","iso.3.6.1.2.1.31.1.1.1.18.2":"","iso.3.6.1.2.1.31.1.1.1.18.3":"","iso.3.6.1.2.1.31.1.1.1.19.1":"0","iso.3.6.1.2.1.31.1.1.1.19.2":"0","iso.3.6.1.2.1.31.1.1.1.19.3":"0"}';
sendMetricTofile($hostname,$dataToBeInserted,$checkName);
*/
?>
