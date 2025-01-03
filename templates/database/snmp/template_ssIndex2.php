<?php
/*
   This template is to parse SNMP kernel details generally RAM and load

   Details that must be sent to file: hostname, metric array for parsing
   All templates are standalone, as we need to make readable and sane Graphite keys

   OID: iso.3.6.1.4.1.2021.11 load statistics
   https://oidref.com/iso.3.6.1.4.1.2021.11

   As this is going in the database, remove the graphite prefixes and hostname stuff
   This is just a data dump of stats
   This is service check ssIndex2 result set
*/

    $dataToBeInserted=json_decode($dataToBeInserted, true);

    /* Create empty array to fill with mapped values */
    $clean=array();
    foreach ($dataToBeInserted as $k => $v) {
      /* Dont get sloppy!  Make the result set as clean as possible */
      if (empty($v)) { $v='';}
      $v = trim($v);

      switch ($k) {
        case "iso.3.6.1.4.1.2021.11.2.0":
          $v = str_replace(array("\r", "\n"), "", $v);
          $clean["ssErrorName"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.3.0":
          $clean["ssSwapIn"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.4.0":
          $clean["ssSwapOut"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.5.0":
          $clean["ssIOSent"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.6.0":
          $clean["ssIOReceive"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.7.0":
          $clean["ssSysInterrupts"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.8.0":
          $clean["ssSysContext"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.9.0":
          $clean["ssCpuUser"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.10.0":
          $clean["ssCpuSystem"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.11.0":
          $clean["ssCpuIdle"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.50.0":
          $clean["ssCpuRawUser"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.51.0":
          $clean["ssCpuRawNice"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.52.0":
          $clean["ssCpuRawSystem"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.53.0":
          $clean["ssCpuRawIdle"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.54.0":
          $clean["ssCpuRawWait"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.55.0":
          $clean["ssCpuRawKernel"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.56.0":
          $clean["ssCpuRawInterrupt"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.57.0":
          $clean["ssIORawSent"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.58.0":
          $clean["ssIORawReceived"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.59.0":
          $clean["ssRawInterrupts"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.60.0":
          $clean["ssRawContexts"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.61.0":
          $clean["ssCpuRawSoftIRQ"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.62.0":
          $clean["ssRawSwapIn"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.63.0":
          $clean["ssRawSwapOut"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.64.0":
          $clean["ssCpuRawSteal"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.65.0":
          $clean["ssCpuRawGuest"]= "$v";
          break;
        case "iso.3.6.1.4.1.2021.11.66.0":
          $clean["ssCpuRawGuestNice"]= "$v";
          break;
      } // end switch
    } // end foreach

  /* Returns clean[keys] => values for example above */
  // echo "JSON encoded array: " . json_encode($clean) . "\n"; // useful to see what we are going to return
  $nonNumericReturns = array();  // Emmpty array of stuff to ignore

  $returnArrayValues=array();
  $graphiteRootKey = '';
  foreach ($clean as $k => $v) {
    if ( ! in_array($k, $nonNumericReturns) && ! $v == '') {
      $graphiteKey1=$k;
      $graphiteValue=$v;
      // At this point we have all data needed to send to Graphite
      $graphiteKey=$graphiteRootKey . $graphiteKey1;
      $returnArrayValues[$graphiteKey] = $graphiteValue;
    } // end if
  }  // end foreach
  $this->returnArrayValues=$returnArrayValues;
  return $this->returnArrayValues;
?>
