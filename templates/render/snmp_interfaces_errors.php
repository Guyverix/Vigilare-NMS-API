<?php
/*
  When called this will attempt to parse the RRD graphs given as a filename arg
  All bandwidth interfaces will behave the same way, however 64 vs 32 bit counters
  use different names.  The math is the same however.

  Certain things WILL be known when this is called.  Specifically the $hostname will exist
  Optional things can be told to us such as timeframes.  Start and End specfically

*/

  require_once('/opt/nmsApi/src/Infrastructure/Shared/Functions/rrdUtilityFunctions.php');

  $hostname='guyver-office.iwillfearnoevil.com';  // DEBUG
  $imagePath='/opt/nmsApi/public/static/';        // config defined for API
  if (empty($start)) { $start='-1d'; }
  if (empty($end)) { $end='now'; }

  //$file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/Realtek_Semiconductor_Co___Ltd__RTL8111_8168_8411_PCI_Express_Gigabit_Ethernet_Controller_32.rrd';
  $file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/enp2s0_64.rrd';

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
  $inOctetColor=colorList(0);
  $outOctetColor=colorList(1);
  $thin='#aa0000';

  // Define the UGLY array of vars to make the graph
  $renderOptions = array( "--start", $start, "--end", $end, "-w 800", "--alt-autoscale-max", "--rigid", "--vertical-label=B/s",
                 "DEF:inoctets=".$file.":".$inOctetName.":AVERAGE",
                 "DEF:outoctets=".$file.":".$outOctetName.":AVERAGE",
                 "DEF:inoctets_max=".$file.":".$inOctetName.":MAX",
                 "DEF:outoctets_max=".$file.":".$outOctetName.":MAX",
                 "CDEF:octets=inoctets,outoctets,+",
                 "CDEF:doutoctets=outoctets,-1,*",
                 "CDEF:outbits=outoctets,8,*",
                 "CDEF:outbits_max=outoctets_max,8,*",
                 "CDEF:doutoctets_max=outoctets_max,-1,*",
                 "CDEF:doutbits=doutoctets,8,*",
                 "CDEF:doutbits_max=doutoctets_max,8,*",
                 "CDEF:inbits=inoctets,8,*",
                 "CDEF:inbits_max=inoctets_max,8,*",
                 "VDEF:totin=inoctets,TOTAL",
                 "VDEF:totout=outoctets,TOTAL",
                 "VDEF:tot=octets,TOTAL",
                 "VDEF:95thin=inbits,95,PERCENT",
                 "VDEF:95thout=outbits,95,PERCENT",
                 "VDEF:d95thout=doutbits,5,PERCENT",
                 'AREA:inbits' .$inOctetColor. ' LINE1.25:inbits#4A8328:"In " GPRINT:inbits:LAST:%6.2lf%s GPRINT:inbits:AVERAGE:%6.2lf%s GPRINT:inbits_max:MAX:%6.2lf%s GPRINT:95thin:%6.2lf%s\\\n',
                 'AREA:doutbits' . $outOctetColor. ' LINE1.25:doutbits#323B7C:"Out" GPRINT:outbits:LAST:%6.2lf%s GPRINT:outbits:AVERAGE:%6.2lf%s GPRINT:outbits_max:MAX:%6.2lf%s GPRINT:95thout:%6.2lf%s\\\n',
                 'GPRINT:tot:"Total %6.2lf%s" GPRINT:totin:"(In %6.2lf%s" GPRINT:totout:"Out %6.2lf%s)\\l" LINE1:95thin' . $thin . ' LINE1:d95thout' .$thin
               );
  // Really the array is not needed, but damn!  We need some way to read this mess
  $renderDetails = implode($renderOptions,' ');

  // Need a discrete image name per host.  Make the jpg match the rrd filename
  $createImageName = explode('/', $file);
  $imageName = end($createImageName);
  $imageName = preg_replace('/rrd/','jpg', $imageName);
  $fullImageName=$hostname ."_". $imageName;


  // Use the Method Luke
  $create=manualGraphMe($imagePath . $fullImageName, $renderDetails);
  if ( $create !== 0) {
    echo $create;
  }
  else {
    echo "rrd graph created " . $fullImageName . "\n";
  }

/*
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
