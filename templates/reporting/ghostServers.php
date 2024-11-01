<?php
/*
  Yes I know that the return from the if will short circuit what comes after
  however since this is an include, oddball results seem to happen.
  We check that $query is set in the main script to confirm
  our checks here worked.

  Odd, but seems to work and be reliable
*/
/*
  This is actually testing a no-arg query.  Return server list of disabled hosts
*/

  $query = "SELECT * FROM Device WHERE productionState > 0";
  $this->db->prepare("$query");
?>
