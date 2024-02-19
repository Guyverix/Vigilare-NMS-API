<?php

// this is our db class defintion
require (__DIR__ . '/../app/Database.php');

// New object created
$db = new Database();

//print_r($db);
//var_dump($db);

// confirm we are connected to database
if (! empty( $db->error)) {
  echo "failed connection: " . $db->error . "\n";
  exit;
}
else {
  echo "successful connection \n";
}

// Set values for object
$db->query("select * from event ORDER BY stateChange DESC LIMIT 20");

// run the query
$db->execute();

/*
all below is dealing with the object data now
This should all be in RAM, and released after
use case is complete, so we do not have leaks
when inside a loop.
*/

// Full result set
$data = $db->resultset();
print_r($data);

// returns only first row of query
$data2 = $db->single();
print_r($data2);

// integer of rows returned
$data3 = $db->rowCount();
echo $data3 . "\n";

$data4 = $db->lastInsertId();
echo $data4;

?>
