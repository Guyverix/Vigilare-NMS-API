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


  $type='Packets/Octets';
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


  if ( preg_match('/_32.rrd/', $file)) {
    $inOctetName="ifInOctets";
    $outOctetName="ifOutOctets";
  }
  elseif ( preg_match('/_64.rrd/', $file)) {
    $inOctetName="HCInOctets";
    $outOctetName="HCOutOctets";
  }
  else {
    // hand-jam rrd file, we are not going to guess
    return "template unable to tell if 32 or 64 bit counters";
  }
  // $thin='#aa0000';

  // Define the UGLY array of vars to make the graph
  $renderOptions = array( "--start", $start, "--end", $end, "-w 800", "--alt-autoscale-max", "--rigid", "--vertical-label=B/s", "--title=\"$title\"",
                 "DEF:inoctets=".$file.":".$inOctetName.":AVERAGE",
                 "DEF:outoctets=".$file.":".$outOctetName.":AVERAGE",
                 "COMMENT:'         Last'",
                 'COMMENT:"  Average"\\\n',
                 'AREA:inoctets' .colorList(0) . ' LINE1.25:inoctets#4A8328:"In " GPRINT:inoctets:LAST:%6.2lf%s GPRINT:inoctets:AVERAGE:%6.2lf%s\\\n',
                 'AREA:outoctets' . colorList(1) . ' LINE1.25:outoctets#323B7C:"Out" GPRINT:outoctets:LAST:%6.2lf%s GPRINT:outoctets:AVERAGE:%6.2lf%s\\\n',
               );
  // Really the array is not needed, but damn!  We need some way to read this mess
  $renderDetails = implode(' ',$renderOptions);

  // Need a discrete image name per host.  Make the jpg match the rrd filename
  $createImageName = explode('/', $file);
  $imageName = end($createImageName);
  $imageName = preg_replace('/rrd/','jpg', $imageName);
  $type=preg_replace('/[ ]/','_', $type);
  $fullImageName=$hostname ."_". $imageName;
  $fullImageName=ltrim(rtrim($fullImageName));


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

?>
