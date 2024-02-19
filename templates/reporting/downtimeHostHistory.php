<?php
/*
  Yes I know that the return from the if will short circuit what comes after
  however since this is an include, oddball results seem to happen.
  We check that $query is set in the main script to confirm
  our checks here worked.

  Odd, but seems to work and be reliable
*/

if ( ! isset($arr['startEvent']) || ! isset($arr['endEvent'])) {
  return "Failure: startEvent or endEvent was not sent for the query";
}
else {
  $query = "SELECT device FROM history WHERE eventName='ping' AND endEvent <= :endEvent AND startEvent >= :startEvent GROUP BY device";
  $this->db->prepare("$query");
  $this->db->bind('startEvent', $arr['startEvent']);
  $this->db->bind('endEvent', $arr['endEvent']);
}
?>
