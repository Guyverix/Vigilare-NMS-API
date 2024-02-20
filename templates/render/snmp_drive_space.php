<?php
/*
  When called this will attempt to parse the RRD graphs given as a filename arg

  Certain things WILL be known when this is called.  Specifically the $file will exist
  Optional things can be told to us such as timeframes.  Start and End specfically.
  While the template can define things like "type" in the future there will be an override
  so we can use adhoc names better

*/

  require_once(__DIR__ . '/../../src/Infrastructure/Shared/Functions/rrdUtilityFunctions.php');

  // Manditory parameters to call this template:
  $type='Drive Space';
//  $hostname='guyver-office.iwillfearnoevil.com';  // DEBUG
  $imagePath=__DIR__ . '/../../public/static/';
  if (empty($start)) { $start='-1d'; }
  if (empty($end)) { $end='now'; }

//  $file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/drive/space/__32.rrd'; // Notice the double _ (the first is the / conversion
//  $file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/drive/space/Physical-memory_32.rrd';
  // Try to get the drive mount named cleanly and added to the title
//  $file = preg_replace('/-/', '\-', $file);
//  $file = "$file";
  $createName=preg_replace('/.*.\//','', $file);
  $createName=preg_replace('/_32.rrd/','', $createName);
  if ( preg_match('/^_/', $createName)) {
    $createName=preg_replace('/_/','/',$createName);
  }
  else {
    $createName=preg_replace('/_/',' ',$createName);
  }
  $createName=preg_replace('/-/',' ',$createName);
  $createName=preg_replace('/\+/',':', $createName);    // Return Windows + back to : for display
//  $createName=preg_replace('/_\-_/',':', $createName);    // Return Windows + back to : for display
  $title = $type . " " . $createName;


  /*
    This is a nice way to suppress graphing on things we dont caare about
    the location is inside the properties for the host.
    https://stackoverflow.com/questions/25229364/php-check-if-array-element-exists-in-any-part-of-the-string
  */
  if ( is_array($ignoreMatch) && ! empty($ignoreMatch)) {
    if( preg_match("(".implode("|",array_map("preg_quote",$ignoreMatch)).")",$createName,$m)) {
      $this->returnArrayValues = ['bypassed file ' . $file];  // returns in UI array['data'][0]
      return $this->returnArrayValues;
    }
  }

  /*
    For each graph point, we are going to use a standard color for clarity, so each AREA will have a defined color based on colorList(#); (start at zero kids)
    If you dont like the colors, go into the Shared/Functions directory and change rrdUtilityFunctions to something you like
  */
  $size='hrStorageSize';
  $used='hrStorageUsed';

  // Define the UGLY array of vars to make the graph
  // Really the array is not needed, but damn!  We need some way to read this mess
  // $renderOptions = array( "--start", $start, "--end", $end, "-w 600", "--vertical-label=Space", "--title=\"$title\"",
  $renderOptions = array( "--start", $start, "--end", $end, "-w 800", "--alt-autoscale-max", "--rigid", "--vertical-label=Space", "--title=\"$title\"",
                 "--lower-limit=0",
                 "DEF:size=" . $file .  ":" . $size . ":AVERAGE",
                 "DEF:used=" . $file .  ":".  $used . ":AVERAGE",
                 "DEF:block=" . $file . ":hrStorageUnit:AVERAGE",
                 "CDEF:realSize=size,block,*",
                 "CDEF:realUsed=used,block,*",
                 'AREA:realSize' . colorList(0) . ' LINE1:realSize' . colorList(0) . ':"Partition Size" GPRINT:realSize:LAST:%6.2lf%s\\\n',
                 'AREA:realUsed' . colorList(1) . ' LINE1:realUsed' . colorList(1) . ':"Used" GPRINT:realUsed:LAST:%6.2lf%s\\\n',
               );


  // Convert the array BACK into an ugly string to pass to rrdtool
  $renderDetails = implode(" ", $renderOptions);

  // Need a discrete image name per host.  Make the jpg match the rrd filename
  $createImageName = explode('/', $file);
  $imageName = end($createImageName);
  $imageName = preg_replace('/rrd/','jpg', $imageName);
  $type=preg_replace('/[ ]/','_', $type);
  $fullImageName=$hostname ."_". $type ."_". $imageName;
  $fullImageName=ltrim(rtrim($fullImageName));

  // Grab the metrics for the last update and create an array with EVERYTHING to return
  $rrdReturnData = returnLastUpdateManual($file);
  $rrdReturnData['startTime'] = $start;
  $rrdReturnData['endTime'] = $end;
  $rrdReturnData['image'] = '/static/' . $fullImageName;
  $rrdReturnData['renderOptions'] = json_encode($renderOptions,1);
  $rrdreturnData['renderString'] = $renderDetails;

  // print_r($rrdReturnData);
  // exit();
  // echo "rrdtool graph " . $imagePath . $fullImageName . " " . $renderDetails . "\n";

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

?>
