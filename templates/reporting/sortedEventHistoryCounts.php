<?php
if ( ! isset($arr)) {
  $parameters = array();
  $parameters[] = 'startEvent';
  echo json_encode($parameters,1);
}
elseif ( ! isset($arr['startEvent'])) {
  return "Failure: startEvent was not sent for the query";
}
else {
  $query="SELECT device, eventName, COUNT(eventSeverity) as count FROM(SELECT device, eventSeverity, eventName FROM history WHERE startEvent >= :startEvent ORDER BY eventName) t1 GROUP BY device, eventName ORDER BY count DESC";
  $this->db->prepare("$query");
  $this->db->bind('startEvent', $arr['startEvent']);
}
?>
