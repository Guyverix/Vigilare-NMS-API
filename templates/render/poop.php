<?php


$foo=' ["--start","-1d","--end","now","-w 800","--alt-autoscale-max","--rigid","--vertical-label=Space","--title=\"Drive Space C: Label: Serial Number ac33b5d2\"","--lower-limit=0","DEF:size=\/opt\/nmsApi\/rrd\/mary-win.iwillfearnoevil.com\/snmp\/drive\/space\/C+-Label+--Serial-Number-ac33b5d2_32.rrd:hrStorageSize:AVERAGE","DEF:used=\/opt\/nmsApi\/rrd\/mary-win.iwillfearnoevil.com\/snmp\/drive\/space\/C+-Label+--Serial-Number-ac33b5d2_32.rrd:hrStorageUsed:AVERAGE","DEF:block=\/opt\/nmsApi\/rrd\/mary-win.iwillfearnoevil.com\/snmp\/drive\/space\/C+-Label+--Serial-Number-ac33b5d2_32.rrd:hrStorageUnit:AVERAGE","CDEF:realSize=size,block,*","CDEF:realUsed=used,block,*","AREA:realSize#00cc00 LINE1:realSize#00cc00:\"Partition Size\" GPRINT:realSize:LAST:%6.2lf%s\\\\n","AREA:realUsed#0000ff LINE1:realUsed#0000ff:\"Used\" GPRINT:realUsed:LAST:%6.2lf%s\\\\n"]';


$bar=json_decode($foo,true);
foreach ($bar as $barf) {
  echo $barf . " ";
}
echo "\n\n";
print_r($bar);



?>
