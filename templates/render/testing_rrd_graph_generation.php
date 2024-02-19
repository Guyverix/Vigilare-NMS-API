<?php


$rrdGraph= new RRDGraph(__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/enp2s0_64.rrd');


$file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/Realtek_Semiconductor_Co___Ltd__RTL8111_8168_8411_PCI_Express_Gigabit_Ethernet_Controller_32.rrd';
//$file=__DIR__ . '/../../rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/enp2s0_64.rrd';



$opts = array( "--start -12h", "--vertical-label=B/s",
                 "DEF:inoctets=".$file.":ifInOctets:AVERAGE DEF:outoctets=".$file.":ifOutOctets:AVERAGE",
                 "AREA:inoctets#00FF00:In traffic",
                 "LINE1:outoctets#0000FF:Out traffic\\r",
                 "CDEF:inbits=inoctets,8,*",
                 "CDEF:outbits=outoctets,8,*",
                 "COMMENT:\\n",
                 "GPRINT:inbits:AVERAGE:Avg In traffic\: %6.2lf %Sbps",
                 "COMMENT:  ",
                 "GPRINT:inbits:MAX:Max In traffic\: %6.2lf %Sbps\\r",
                 "GPRINT:outbits:AVERAGE:Avg Out traffic\: %6.2lf %Sbps",
                 "COMMENT: ",
                 "GPRINT:outbits:MAX:Max Out traffic\: %6.2lf %Sbps\\r"
               );
 



//rrdtool graph /tmp/XJpXnmvA2XcQo1kV.png --alt-autoscale-max --rigid -E --start 1686525082 --end 1686611482 --width 1159 --height 300 -c BACK#EEEEEE00 -c SHADEA#EEEEEE00 -c SHADEB#EEEEEE00 -c FONT#000000 -c CANVAS#FFFFFF00 -c GRID#a5a5a5 -c MGRID#FF9999 -c FRAME#5e5e5e -c ARROW#5e5e5e -R normal --font LEGEND:8:'DejaVuSansMono' --font AXIS:7:'DejaVuSansMono' --font-render-mode normal 
// COMMENT:'Bits/s Now Avg Max 95th \n'
// DEF:outoctets=/opt/observium/rrd/guyver-office.iwillfearnoevil.com/port-2.rrd:OUTOCTETS:AVERAGE 
// DEF:inoctets=/opt/observium/rrd/guyver-office.iwillfearnoevil.com/port-2.rrd:INOCTETS:AVERAGE 
// DEF:outoctets_max=/opt/observium/rrd/guyver-office.iwillfearnoevil.com/port-2.rrd:OUTOCTETS:MAX 
// DEF:inoctets_max=/opt/observium/rrd/guyver-office.iwillfearnoevil.com/port-2.rrd:INOCTETS:MAX 
// CDEF:octets=inoctets,outoctets,+ CDEF:doutoctets=outoctets,-1,* CDEF:outbits=outoctets,8,* CDEF:outbits_max=outoctets_max,8,* CDEF:doutoctets_max=outoctets_max,-1,* CDEF:doutbits=doutoctets,8,* CDEF:doutbits_max=doutoctets_max,8,* CDEF:inbits=inoctets,8,* CDEF:inbits_max=inoctets_max,8,*
// VDEF:totin=inoctets,TOTAL VDEF:totout=outoctets,TOTAL VDEF:tot=octets,TOTAL VDEF:95thin=inbits,95,PERCENT VDEF:95thout=outbits,95,PERCENT VDEF:d95thout=doutbits,5,PERCENT 
// AREA:inbits#92B73F LINE1.25:inbits#4A8328:'In ' GPRINT:inbits:LAST:%6.2lf%s GPRINT:inbits:AVERAGE:%6.2lf%s GPRINT:inbits_max:MAX:%6.2lf%s GPRINT:95thin:%6.2lf%s\\n AREA:doutbits#7075B8 
// LINE1.25:doutbits#323B7C:'Out' GPRINT:outbits:LAST:%6.2lf%s GPRINT:outbits:AVERAGE:%6.2lf%s GPRINT:outbits_max:MAX:%6.2lf%s
// GPRINT:95thout:%6.2lf%s\\n GPRINT:tot:'Total %6.2lf%s' GPRINT:totin:'(In %6.2lf%s' GPRINT:totout:'Out %6.2lf%s)\\l' LINE1:95thin#aa0000 LINE1:d95thout#aa0000 


$opts2 = array( "--start", "-1d", "--alt-autoscale-max", "--rigid", "--vertical-label=B/s",
                 "DEF:inoctets=".$file.":ifInOctets:AVERAGE",
                 "DEF:outoctets=".$file.":ifOutOctets:AVERAGE",
                 "DEF:inoctets_max=".$file.":ifInOctets:MAX",
                 "DEF:outoctets_max=".$file.":ifOutOctets:MAX",
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
                 'AREA:inbits#92B73F LINE1.25:inbits#4A8328:"In " GPRINT:inbits:LAST:%6.2lf%s GPRINT:inbits:AVERAGE:%6.2lf%s GPRINT:inbits_max:MAX:%6.2lf%s GPRINT:95thin:%6.2lf%s\\\n',
                 'AREA:doutbits#7075B8 LINE1.25:doutbits#323B7C:"Out" GPRINT:outbits:LAST:%6.2lf%s GPRINT:outbits:AVERAGE:%6.2lf%s GPRINT:outbits_max:MAX:%6.2lf%s GPRINT:95thout:%6.2lf%s\\\n',
                 'GPRINT:tot:"Total %6.2lf%s" GPRINT:totin:"(In %6.2lf%s" GPRINT:totout:"Out %6.2lf%s)\\l" LINE1:95thin#aa0000 LINE1:d95thout#aa0000'
               );
print_r($opts2);
$opts3=implode($opts2,' ');
echo "rrdtool graph /opt/nmsGui/public/event/enp2s0_2.jpg  ". $opts3 ;

echo "\n\n\n";
echo "rrdtool graph /opt/nmsGui/public/event/enp2s0_2.jpg ";
foreach ( $opts2 as $opt) {
  echo $opt . " ";
}

//  $ret = rrd_graph("enp2s0.jpg", $opts, count($opts));
  $ret = rrd_graph("/opt/nmsGui/public/event/enp2s0.jpg", $opts);
  if( !is_array($ret) )
  {
    $err = rrd_error();
    echo "rrd_graph() ERROR: $err\n";
  }


  $ret2 = rrd_graph("/opt/nmsGui/public/event/enp2s0_2.jpg", $opts2);
  if( !is_array($ret2) )
  {
    $err = rrd_error();
    echo "rrd_graph() ERROR: $err\n";
  }



?>
