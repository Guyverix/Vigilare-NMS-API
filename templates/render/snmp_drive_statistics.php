<?php
/*
  When called this will attempt to parse the RRD graphs given as a filename arg

  Certain things WILL be known when this is called.  Specifically the $file will exist
  Optional things can be told to us such as timeframes.  Start and End specfically.
  While the template can define things like "type" in the future there will be an override
  so we can use adhoc names better

*/

  require_once('/opt/nmsApi/src/Infrastructure/Shared/Functions/rrdUtilityFunctions.php');

  // Manditory parameters to call this template:
  $type='drive-io';

  // $hostname='guyver-office.iwillfearnoevil.com';  // DEBUG
  $imagePath='/opt/nmsApi/public/static/';        // config defined for API This is going to have to go into a config file elsewhere
  if (empty($start)) { $start='-1d'; }
  if (empty($end)) { $end='now'; }

  //  $file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/drive/space/__32.rrd'; // Notice the double _ (the first is the / conversion
  //  $file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/drive/space/Physical-memory_32.rrd';
  // Try to get the drive mount named cleanly and added to the title
  $createName=preg_replace('/.*.\//','', $file);           // Remove everything before last / char (filename only)
  $createName=preg_replace('/_32.rrd/','', $createName);   // Strip _32.rrd
  $createName=preg_replace('/_64.rrd/','', $createName);   // Strip _64.rrd
  $createName=preg_replace('/_/','/',$createName);         // change _ to /
  $createName=preg_replace('/-/',' ',$createName);         // change - to ' ' (space)
  $title = $type . " " . $createName;                      // attempt to make useful title

  /*
    This is a nice way to suppress graphing on things we dont caare about
    the location is inside the properties for the host.
    https://stackoverflow.com/questions/25229364/php-check-if-array-element-exists-in-any-part-of-the-string
  */
  if (is_array($ignoreMatch) && ! empty($ignoreMatch)) {
    if( preg_match("(".implode("|",array_map("preg_quote",$ignoreMatch)).")",$createName,$m)) {
      $this->returnArrayValues = ['bypassed file ' . $file];  // returns in UI array['data'][0]
      return $this->returnArrayValues;
    }
  }


  /*
    For each named DS in RRD make a name here for clarity instead of hard-coding in the array
    Only make a var for things you are graphing.  No need to define all values inside the rrd
  */
  $reads='diskIOReads';
  $writes='diskIOWrites';

  /*
     Define the UGLY array of vars to make the graph
     Really the array is not needed, but damn!  We need some way to read this mess
  */
  $renderOptions = array( "--start", $start, "--end", $end, "-w 800", "--alt-autoscale-max", "--rigid", "--vertical-label=Accesses", "--title=\"$title\"",
                 "--lower-limit=0",
                 "DEF:reads=".$file.":".$reads.":AVERAGE",
                 "DEF:writes=".$file.":".$writes.":AVERAGE",
                 'AREA:reads' . colorList(0) . ' LINE1:reads' . colorList(0) .   ':"Drive Reads " GPRINT:reads:LAST:%6.2lf%s\\\n',
                 'AREA:writes' . colorList(1) . ' LINE1:writes' . colorList(1).  ':"Drive Writes" GPRINT:writes:LAST:%6.2lf%s\\\n',
               );

  // Convert the array BACK into an ugly string to pass to rrdtool
  $renderDetails = implode(" ", $renderOptions);
  // echo $renderDetails . "\n"; // DEBUG


  // Need a discrete image name per host.  Make the jpg match the rrd filename
  $createImageName = explode('/', $file);
  $imageName = end($createImageName);
  $imageName = preg_replace('/rrd/','jpg', $imageName);
  $fullImageName=$hostname ."_". $type ."_". $imageName;
  $fullImageName=ltrim(rtrim($fullImageName));

  // Grab the metrics for the last update and create an array with EVERYTHING to return
  $rrdReturnData = returnLastUpdateManual($file);
  $rrdReturnData['startTime'] = $start;
  $rrdReturnData['endTime'] = $end;
  $rrdReturnData['image'] = '/static/' . $fullImageName;

  // print_r($rrdReturnData); // DEBUG
  // exit();  // DEBUG
  // echo "rrdtool graph " . $imagePath . $fullImageName . " " . $renderDetails . "\n";  // DEBUG

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
