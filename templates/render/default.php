<?php
/*
  The intent of this is to be a catchall or default render for an rrd which does not
  have a template defined for it.  This is the render of last resort.
*/

  $this->logger->debug("default.php loaded require file");

  if ( ! isset($logger)) {
    global $logger;
  }

  require_once(__DIR__ . '/../../src/Infrastructure/Shared/Functions/rrdUtilityFunctions.php');

  /*
  // TEST VALUES for when a template does not exist at all
  $file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/Realtek_Semiconductor_Co___Ltd__RTL8111_8168_8411_PCI_Express_Gigabit_Ethernet_Controller_32.rrd';
  $file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/enp2s0_64.rrd';
  $file=__DIR__ . '/../../rrd/webserver01.iwillfearnoevil.com/snmp/memory/memory_64.rrd';
  $hostname='webserver01.iwillfearnoevil.com';
  */

  $imagePath= __DIR__ . '/../../public/static/';        // config defined for API
  if (empty($ignoreMatch)) { $ignoreMatch = ''; } // set something if not defined
  if (empty($start)) { $start='-1d'; }
  if (empty($end)) { $end='now'; }



  $listOfMetrics = rrd_lastupdate($file);
  // array ds_navm contains names, data contains last update values
  // echo print_r($listOfMetrics,true);  // DEBUG

  foreach($listOfMetrics['ds_navm'] as $metricNames) {
      $createName=preg_replace('/.*.\//','', $file);
      $createName=preg_replace('/_32.rrd/','', $createName);
      $createName=preg_replace('/_64.rrd/','', $createName);
      $createName=preg_replace('/_/','/',$createName);
      $createName=preg_replace('/\//',' ',$createName);
      $createName=preg_replace('/-/',' ',$createName);
      $metric[] = array( 'name' => $metricNames, 'createName' => $createName, 'title' => $createName, 'metricName' => $metricNames );
  }
  // echo print_r($metric, true);  // DEBUG
  // exit();

  if (is_array($ignoreMatch) && ! empty($ignoreMatch)) {
    if( preg_match("(".implode("|",array_map("preg_quote",$ignoreMatch)).")",$createName,$m)) {
      $this->returnArrayValues = ['bypassed file ' . $file];  // returns in UI array['data'][0]
      return $this->returnArrayValues;
    }
  }

  // Walk through each metric and create an array of results
  foreach ($metric as $singleMetric) {
    $mergeInfo = array();
    // Define the UGLY array of vars to make the graph
    $renderOptions = array( "--start", $start, "--end", $end, "-w 800", "--alt-autoscale-max", "--rigid", "--vertical-label=count", "--title=\"" . $singleMetric['title'] . "\"",
                            "DEF:" . $singleMetric['metricName'] . "=" . $file . ":" . $singleMetric['metricName'] . ":AVERAGE",
                            'AREA:' . $singleMetric['metricName'] . colorList(0) . ' LINE1:' . $singleMetric['metricName']  . colorList(0) . ':"' . $singleMetric['metricName'] . '" GPRINT:' . $singleMetric['metricName'] . ':LAST:%6.2lf%s\\\n', );

    // Really the array is not needed, but damn!  We need some way to read this mess
    $renderDetails = implode(' ',$renderOptions);

    // Need a discrete image name per metric.
    $createImageName = explode('/', $file);
    $imageName = end($createImageName);
    $imageName = preg_replace('/:/', '', $imageName); 
    $imageName = preg_replace('/rrd/','jpg', $imageName);
    $fullImageName=$hostname ."_". $singleMetric['metricName'] ."_". $imageName;
    // Grab the metrics for the last update and create an array with EVERYTHING to return
    $mergeInfo = returnLastUpdateManual($file);
    $mergeInfo += array('startTime' => $start);
    $mergeInfo += array('endTime' => $end);
    $mergeInfo += array('image' => '/static/' . $fullImageName);
    $rrdReturnData[] = $mergeInfo;
    // Use the Method Luke
    //echo "Image Path " . $imagePath . "\n";
    //echo "Full Image Name " . $fullImageName . "\n";
    //echo "Rendering Details " . $renderDetails . "\n";

    $create = manualGraphMe($imagePath . $fullImageName, $renderDetails);
    if ( $create !== 0) {  // Any errs at all, return the errors as best we can
      //echo "Create " . json_encode($create,1) . "\n";
      $this->returnArrayValues=$create;
      return $this->returnArrayValues;
    }
  }
  // echo print_r($rrdReturnData);  //DEBUG
  // exit();
  // Comment me out if we are in testing mode
  //    print_r($rrdReturnData); //
  $this->returnArrayValues=$rrdReturnData;
  return $this->returnArrayValues;


/*
  // Testing only, do not use live or stuff may break
  echo "rrdtool graph " . $imagePath . $fullImageName ." ". $renderDetails ; // DEBUG
  $cmd="rrdtool graph " . $imagePath . $fullImageName ." ". $renderDetails ;
  $result=exec($cmd, $output, $exitCode);
  if($exitCode !== 0 ) {
    $err = rrd_error();
    echo "rrd graph() ERROR: " . print_r($output) . "\n";
  }
  else {
    echo "rrd graph created " . $fullImageName . "\n";
  }
*/
?>
