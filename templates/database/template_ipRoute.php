<?php
/*
   walk oid: 1.3.6.1.2.1.4.21.1
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
  $list=array();

  // strpos is useful, but remember the period at the stop point of the oid value
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


  /* Create empty array to fill with mapped values */
  $mapped=array();
  $clean=array();
  foreach ($list as $metric) {
    // echo "Metric VALUE = ". $metric . "\n"; // DEUBG
    foreach ($dataToBeInserted as $k => $v) {
      // echo "VALUE KEY " . $k . " VALUE " . $v . "\n"; // DEBUG
      if (empty($v)) { $v='0';}
      switch ($k) {
        case "iso.3.6.1.2.1.4.21.1.2.$metric":
          $clean[$metric]['routeIndex'] = $v ;
          break;
        case "iso.3.6.1.2.1.4.21.1.3.$metric":
          $clean[$metric]['routeMetric'] = $v ;
          break;
        case "iso.3.6.1.2.1.4.21.1.7.$metric":
          $clean[$metric]['routeNextHop'] = $v ;
          break;
        case "iso.3.6.1.2.1.4.21.1.8.$metric":
          if ( $v == 4 ) { $v = "indirect"; }
          if ( $v == 3 ) { $v = "direct"; }
          $clean[$metric]['routeType'] = $v ;
          break;
        case "iso.3.6.1.2.1.4.21.1.9.$metric":
          $v=routeProtocol($v);
          $clean[$metric]['routeProto'] = $v ;
          break;
        case "iso.3.6.1.2.1.4.21.1.11.$metric":
          $clean[$metric]['routeNetmask'] = $v ;
          break;
        default:
          break;
      } // end switch
    }  // end foreach
    $mapped[$metric] = $clean[$metric];
  } // end foreach


  /* Returns mapped[keys] => values for example above */
   // print_r($mapped);  // DEBUG
  // exit();  // DEBUG

  // for database inserts we give JSON, not array values.
//  $this->returnArrayValues=json_encode($mapped,True);
  $this->returnArrayValues=$mapped;
  return $this->returnArrayValues;
?>
