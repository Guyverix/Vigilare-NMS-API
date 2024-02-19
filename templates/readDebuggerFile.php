#!/usr/bin/php
<?php
/*
  Just a generic utility that converts the stored JSON file back
  to an array from the debugger.  This can be used for any JSON
  that is stored in a file to convert to an array.
*/

$cliOptions= getopt("f:");
if (isset($cliOptions['f'])) {
  $fileName = $cliOptions['f'];
}
else {
  echo "Filename is a manditory parameter.  Use -f <filename>\n";
  exit();
}

if ( ! file_exists($fileName)) {
  echo "Unable to find file " . $fileName . "\n";
  exit();
}

echo "Pulling from file and converting back from JSON to array output\n";
$allDataConvert = file_get_contents("$fileName");


$allDataConvert = json_decode($allDataConvert,1);
print_r($allDataConvert);
echo "\n\n";

$insert = json_encode($allDataConvert['dataToBeInserted'],1);
echo "Insert data as JSON\n";
echo $insert;

echo "\n\n";
print_r($allDataConvert['dataToBeInserted']);

$test=json_decode($allDataConvert['dataToBeInserted'], true);

echo "\nFOREACH\n";
//foreach ($allDataConvert['dataToBeInserted'] as $k => $v) {
foreach ($test as $k => $v) {
  echo "Key " . $k . " Value " . $v . "\n";
}

$jsonData=json_encode($test,1); 
echo "JSON dataToBeInserted\n\n";
echo $jsonData;

?>
