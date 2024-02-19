/*
   Only these three values are critical
   $this->returnArrayValues  // our array return for the object
   $hostname                 // fqdn or IP of host
   $dataToBeInserted                // The array of data to parse in json_encoded format

   The file name MUST match $oidCheck value so the main script can find this file.

   Hard drive statistics 1.3.6.1.4.1.2021.13.15.1

repl
3.6.1.2.1.2.2.1
3.6.1.4.1.2021.13.15.1.1

*/

  $hostname=preg_replace('/\./', '_', $hostname);
  $dataToBeInserted=json_decode($dataToBeInserted, true);

  /* create an empty array of our index values */
  $list=array();
  $mapped=array();
  $clean=array();

  foreach ($dataToBeInserted as $k => $v) {
    if ( strpos($k, '.3.6.1.4.1.2021.13.15.1.1.1.') !==false) {
      $tempIndexNumber=preg_replace('/.*.3.6.1.4.1.2021.13.15.1.1.1./','', $k);
      $list[]=$tempIndexNumber;
      // Results will simply be integers that we use later
    } // end if
  }  // end foreach
  /* test our array is correct, key => index number */
   //print_r($list); // DEBUG
   //exit();  // DEBUG

  /* Create empty array to fill with mapped values */
  $mapped=array();

  foreach ($list as $searchValue) {
    foreach ($dataToBeInserted as $k => $v) {
      switch ($k) {
        case "iso.3.6.1.4.1.2021.13.15.1.1.2." .$searchValue:
          $v=trim($v, " ");
          $v=preg_replace('/[ .,\/]/', '_', $v);
          $v=preg_replace('/"/', '', $v);
          $clean[$interface]['diskIODevice']= "$v" ;  // string
          break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.3.$searchValue":
         $clean[$interface]['diskIONRead']= "$v" ; // counter32
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.4.$searchValue":
         $clean[$interface]['diskIONWritten']= "$v" ; // counter32
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.5.$searchValue":
         $clean[$interface]['diskIOReads']= "$v" ;  // counter32
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.6.$searchValue":
         $clean[$interface]['diskIOWrites']= "$v" ;  // counter32
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.9.$searchValue":
         $clean[$interface]['diskIOLA1']= "$v" ;  // int
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.12.$searchValue":
         $clean[$interface]['diskIONReadX']= "$v" ; // counter
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.13.$searchValue":
         $clean[$interface]['diskIONWrittenX']= "$v" ; // counter
         break;
       case "iso.3.6.1.4.1.2021.13.15.1.1.14.$searchValue":
         $clean[$interface]['diskIOBusyTime']= "$v" ; // couter
         break;
    $mapped[]= $clean[$searchValue];
  }

  /* Returns maped[0-2][keys] => values for example above */
  //print_r($mapped);

  /* Returns that are NOT numeric values or we specifically dont care about */
  $nonNumericReturns=array("diskIoDevice");

  /* Now loop through each interface and create our metric key and value pair */
  foreach ($mapped as $searchValue) {
    $graphiteRootKey=$hostname . ".snmp.drive." . $searchValue['diskIoDevice'];
    foreach ($searchValue as $k => $v) { 
      /* Match against only values that are numeric */
      if (! in_array($k, $nonNumericReturns)) {
        $graphiteKeyK   = $k;
        $graphiteValueK = $v;
        /* At this point we have all data needed to send to Graphite */
        $graphiteKey = $graphiteRootKey . "." . $graphiteKeyK;
        // echo $graphiteRootKey.".".$graphiteKey." ". $graphiteValue. "\n";
        $returnArrayValues[$graphiteKey] = $graphiteValueV;
      }
    }
  }
  $this->returnArrayValues=$returnArrayValues;
  // print_r($this->returnArrayValues); // DEBUG
  return $this->returnArrayValues;

