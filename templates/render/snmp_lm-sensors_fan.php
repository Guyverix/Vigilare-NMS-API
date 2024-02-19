<?php
/*
  When called this will attempt to parse the RRD graphs given as a filename arg
  All bandwidth interfaces will behave the same way, however 64 vs 32 bit counters
  use different names.  The math is the same however.

  Certain things WILL be known when this is called.  Specifically the $hostname will exist
  Optional things can be told to us such as timeframes.  Start and End specfically

*/



  require_once(__DIR__ . '/../../src/Infrastructure/Shared/Functions/rrdUtilityFunctions.php');

  // $hostname='guyver-office.iwillfearnoevil.com';  // DEBUG
  //$file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/Realtek_Semiconductor_Co___Ltd__RTL8111_8168_8411_PCI_Express_Gigabit_Ethernet_Controller_32.rrd';
  // $file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/enp2s0_64.rrd';

  $imagePath='/opt/nmsApi/public/static/';        // config defined for API
  if (empty($start)) { $start='-1d'; }
  if (empty($end)) { $end='now'; }


  $type='Fan';
  $createName=preg_replace('/.*.\//','', $file);
  $createName=preg_replace('/_32.rrd/','', $createName);
  $createName=preg_replace('/_64.rrd/','', $createName);
  $createName=preg_replace('/_/','/',$createName);
  $createName=preg_replace('/\//',' ',$createName);
  $createName=preg_replace('/-/',' ',$createName);

  $title = $type . " " . $createName;


  if (is_array($ignoreMatch) && ! empty($ignoreMatch)) {
    if( preg_match("(".implode("|",array_map("preg_quote",$ignoreMatch)).")",$createName,$m)) {
      $this->returnArrayValues = ['bypassed file ' . $file];  // returns in UI array['data'][0]
      return $this->returnArrayValues;
    }
  }

  $metricName='fan';
  // $thin='#aa0000';

  // Define the UGLY array of vars to make the graph
  $renderOptions = array( "--start", $start, "--end", $end, "-w 800", "--alt-autoscale-max", "--rigid", "--vertical-label=rpm", "--title=\"$title\"",
                 "DEF:fan=".$file.":".$metricName.":AVERAGE",
                 'AREA:fan' .colorList(0) . ' LINE1:fan' . colorList(0) . ':"RPM " GPRINT:fan:LAST:%6.2lf%s\\\n',
               );
  // Really the array is not needed, but damn!  We need some way to read this mess
  $renderDetails = implode(' ',$renderOptions);

  // Need a discrete image name per host.  Make the jpg match the rrd filename
  $createImageName = explode('/', $file);
  $imageName = end($createImageName);
  $imageName = preg_replace('/:/', '', $imageName); 
  $imageName = preg_replace('/rrd/','jpg', $imageName);
  $fullImageName=$hostname ."_". $type ."_". $imageName;

  // Grab the metrics for the last update and create an array with EVERYTHING to return
  $rrdReturnData = returnLastUpdateManual($file);
  $rrdReturnData['startTime'] = $start;
  $rrdReturnData['endTime'] = $end;
  $rrdReturnData['image'] = '/static/' . $fullImageName;

  // Use the Method Luke
  $create=manualGraphMe($imagePath . $fullImageName, $renderDetails);
  if ( $create !== 0) {
    $this->returnArrayValues=$create;
    return $this->returnArrayValues;
  }
  else {
    //    print_r($rrdReturnData); //
    $this->returnArrayValues=$rrdReturnData;
    return $this->returnArrayValues;
  }

/*
  // Testing, not needed
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

