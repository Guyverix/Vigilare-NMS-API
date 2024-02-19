<?php
/*
  Class definitions for all currently supported types of Metric Storage available
*/


/*
  A secondary graphing option that some users may wish to use.
  Getting good URL's is a PITA for this however.  Likely a table will
  need to be created for each graph type to ensure consistency
*/
if ( ! isset($logger)) {
  global $logger;
}

class MetricParsingGraphite {
  public $returnArrayValues;

  public function parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
    if ( $type == "nrpe" ) {
      if (file_exists(__DIR__ . "/graphite/nrpe/template_" . $type . ".php")) {
        require __DIR__ . "/graphite/nrpe/template_" . $type . ".php";
        return 0;
      }
      else {
        return "Failed to load template file successfully";
      }
    }
    elseif ( $type == "poller" ) {
      if (file_exists(__DIR__ . "/graphite/poller/template_" . $type . ".php")) {
        require __DIR__ . "/graphite/poller/template_" . $type . ".php";
        return 0;
      }
      else {
        return "Failed to load template file successfully";
      }
    }
    // this requires both checkName AND checkAction to be set
    elseif ( ($type == "snmp" || $type == "get" || $type == "walk") && ! is_null( $checkAction) ) {
      if (file_exists(__DIR__ . "/graphite/snmp/template_" . $checkName . ".php")) {
        require __DIR__ . "/graphite/snmp/template_" . $checkName . ".php";
        return 0;
      }
      if (file_exists(__DIR__ . "/graphite/snmp/template_" . $checkAction . ".php")) {
        require __DIR__ . "/graphite/snmp/template_" . $checkAction . ".php";
        return 0;
      }
      else {
        return "Failed to load template file successfully";
      }
    }
    // alive is kinda loosy goosey.  checkName should define the filename
    // however there may be odd checks where it is more accurate to use checkAction
    elseif ( $type == "alive" ) {
      if (file_exists(__DIR__ . "/graphite/alive/template_" . $checkName . ".php")) {
        require __DIR__ . "/graphite/alive/template_" . $checkName . ".php";
        return 0;
      }
      elseif (file_exists(__DIR__ . "/graphite/alive/template_" . $checkAction . ".php")) {
        require __DIR__ . "/graphite/alive/template_" . $checkAction . ".php";
        return 0;
      }
      else {
        return "Failed to load template file successfully";
      }
    }
    // For graphite we have exhausted all options..  Error out
    else {
      return "Failed to load template file successfully";
    }
  }
}

/*
  Most common use will be for slow iteration monitors and data storage of details
  of Devices.  This will REQURE either checkName defined in database directory OR
  checkAction if it is defaulting to graphite SNMP returns
*/
class MetricParsingDatabase {
  public $returnArrayValues;

  public function parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
    if (file_exists(__DIR__ . "/database/template_" . $checkName . ".php")) {
      // This should return our array
      require __DIR__ . "/database/template_" . $checkName . ".php";
      return 0;
    }
    elseif (file_exists(__DIR__ . "/database/snmp/template_" . $checkName . ".php")) {
      // This should return our array
      require __DIR__ . "/database/snmp/template_" . $checkName . ".php";
      return 0;
    }
    elseif (file_exists(__DIR__ . "/database/snmp/template_" . $checkAction . ".php")) {
      // This should return our array
      require __DIR__ . "/database/snmp/template_" . $checkAction . ".php";
      return 0;
    }
    else{
      return "Failed to load template file successfully";
    }
  }
}

/*
  Most likely the most common metric storage that will
  be used.  Graphite has lost a lot of popularity, and I am unsure why.
  type and cycle may have to be defined in the template if they are not passed.
  checkAction is generally not needed for RRD except for fallback
*/
class MetricParsingRrd {
  public $returnArrayValues;

  //  public function parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle) {
  public function parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
    global $logger;
    // Poller and alive may have cycle defined in the checkName value.  Retrieve via explode in template
    if (is_null($cycle) && ( $type !== 'poller' || $type !== "alive")) { return "If cycle is undefined, type becomes manditory"; }

    if (is_null($type) ) {
      if (file_exists(__DIR__ . "/rrd/template_rrd_" . $checkName . ".php")) {
        return 0;
      }
      else {
        return "Failed to load template file successfully";
      }
    }
    elseif ( $type == "nrpe") {
      if (file_exists(__DIR__ . "/rrd/template_rrd_" . $type . "_gague.php")) {
        require __DIR__ . "/rrd/template_rrd_" . $type . "_gague.php";
        return 0;
      }
      else {
        return "Failed to load template file successfully";
      }
    }
    elseif ( $type == "snmp" || $type == "walk" || $type == "get") {
      if (file_exists(__DIR__ . "/rrd/template_rrd_" . $checkName . ".php")) { // Hopefully we can transition to this fully
        require __DIR__ . "/rrd/template_rrd_" . $checkName . ".php";
        return 0;
      }
      elseif (file_exists(__DIR__ . "/rrd/template_rrd_" . $checkAction . ".php")) { // Catch old version
        $logger->debug("generalMetricClass.php MetricParsingRrd found file /rrd/template_rrd_" . $checkAction . ".php for checkName " . $checkName);
        require __DIR__ . "/rrd/template_rrd_" . $checkAction . ".php";
        return 0;
      }
      else {
        return "Failed to load template file successfully";
      }
    }
    elseif ( $type == "poller") {
      if (is_null($cycle)) {
        $findCycle=explode('-',$checkName);
        $cycle=$findCycle[1];
      }
      if (file_exists(__DIR__ . "/rrd/template_rrd_" . $type . "_gague.php")) {
        require __DIR__ . "/rrd/template_rrd_" . $type . "_gague.php";
        return 0;
      }
      else {
        return "Failed to load template file successfully";
      }
    }
    elseif ( $type == "alive") {
      if (file_exists(__DIR__ . "/rrd/template_rrd_" . $type . "_gague.php")) {
        require __DIR__ . "/rrd/template_rrd_" . $type . "_gague.php";
        return 0;
      }
      else {
        return "Failed to load template file successfully";
      }
    }
    else {
      // No match on files or logic.  Error out here.
      return "Ran out of types to match against.  Possible typo for type in database";
    }
  }
}

/*
  Simplistic, and should also include all vars passed for now.
  A generic definition will be needed for other filesystem saves outside
  of metric storage and testing
*/
class MetricParsingFile {
  public $returnArrayValues;

  public function parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
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
        return 0;
      }
      else {
        return "Unable to touch filename " . $fileName;
      }
    }
  }
}

/*
  Use this class to debug issues not getting into other storage types
*/
class MetricParsingFileDebugger {
  public $returnArrayValues;

  public function parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
    $fileName =__DIR__ . "/../file/" . $hostname . "/debugger/" . $checkName . ".txt";
    if (file_exists($fileName)) {
      return 0;
    }
    else {
      $pathParts = pathinfo($fileName);
      if ( ! file_exists($pathParts['dirname'])) {
        mkdir($pathParts['dirname'],0777, true);
      }
      if (touch($fileName)) {
        return 0;
      }
      else {
        return "Unable to touch filename " . $fileName;
      }
    }
  }
}

/*
  TODO begin class for data insertion into influx database
*/
class MetricParsingInflux {
  public $returnArrayValues;

  public function parseRaw($hostname, $dataToBeInserted, $checkName, $checkAction = null, $type = null, $cycle = null) {
    return "InfluxDb Class is not built yet.  Please play again";
  }
}

?>
