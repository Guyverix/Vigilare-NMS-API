<?php
/*
   walk oid: 1.3.6.1.4.1.9.9.13.1.3 Cisco Environment results
   This return is intended for databaseMetric via a slow iteration cycle
   Dont forget that returned values have oid. instead of 1. because I am dumb
*/

  if ( ! is_array($dataToBeInserted)) {
    $dataToBeInserted=json_decode($dataToBeInserted, true);
  }
  /*
    checkName in this case for databaseMetric is actually the called oid for the walk.
    cycle is irrelivant for saving data into the database
    type is also irrelivant.
  */

  /* create an empty array of our index values */
/*
  $list=array();
*/

  // strpos is useful, but remember the period at the stop point of the oid value
  // This is only used to get indexed array values, if you do not have them, ignore this
/*
  foreach ($dataToBeInserted as $k => $v) {
    if (strpos($k, '.3.6.1.2.1.4.21.1.1.') !==false) {
      $metricIndexNumber=preg_replace('/.*.3.6.1.2.1.4.21.1.1./','', $k);
      // In this case the index is actually IP addresses.
      $v = trim($v);  // Make damn sure there is no whitespace
      $list[]=$v;
    } // end if
  }  // end foreach
  // echo "IP Address array: \n" ; // DEBUG
  // print_r($list); // DEBUG
*/

  /* Create empty array to fill with mapped values */
  $mapped=array();
  foreach ($dataToBeInserted as $k => $v) {
    // echo "VALUE KEY " . $k . " VALUE " . $v . "\n"; // DEBUG
    if (empty($v)) { $v='0';}
    switch ($k) {
      case "iso.3.6.1.4.1.9.9.13.1.3.1.2.1005":
        $mapped['ciscoEnvMonTemperatureStatusDescr'] = $v ;
        break;
      case "iso3.6.1.4.1.9.9.13.1.3.1.3.1005":
        $mapped['ciscoEnvMonTemperatureStatusValue'] = $v ;
        break;
      case "iso.3.6.1.4.1.9.9.13.1.3.1.4.1005":
        $mapped['ciscoEnvMonTemperatureThreshold'] = $v ;
        break;
      case "iso.3.6.1.4.1.9.9.13.1.3.1.6.1005":
        if ( $v == 1 ) { $v = "normal"; }
        else { $v = "abnormal"; }
        $mapped['ciscoEnvMonTemperatureState'] = $v ;
        break;
      case "iso.3.6.1.4.1.9.9.13.1.3.1.5.1005":
        $mapped['ciscoEnvMonTemperatureLastShutdown'] = $v ;
        break;
      default:
        break;
    } // end switch
  }  // end foreach


  /* Returns mapped[keys] => values for example above */
  // print_r($mapped);  // DEBUG
  // exit();  // DEBUG

  // for database inserts we give JSON, not array values.
  // $this->returnArrayValues=json_encode($mapped,True);
  $this->returnArrayValues=$mapped;
  return $this->returnArrayValues;
?>
