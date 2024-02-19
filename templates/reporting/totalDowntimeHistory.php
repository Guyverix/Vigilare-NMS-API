<?php

if ( ! isset($arr['startEvent']) || ! isset($arr['endEvent'])) {
  return "Manditory filters not set: startEvent or endEvent";
}
else {
  $query="SELECT SUM(downtime) as totalDowntime, device FROM (
        SELECT TIMESTAMPDIFF(minute, startEvent, endEvent) AS downtime, h.device FROM history h WHERE h.endEvent <= :endEvent AND h.startEvent >= :startEvent AND h.eventName='ping' order by h.device
        ) t1
        GROUP by device";
  $this->db->prepare("$query");
  $this->db->bind('startEvent', $arr['startEvent']);
  $this->db->bind('endEvent', $arr['endEvent']);
}
?>
