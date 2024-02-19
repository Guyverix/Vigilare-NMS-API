<?php
require __DIR__ . '/../opt/nmsApi/app/Curl.php';

$key = '1234fake5';
$apiUrl = 'https://larvel01.iwillfearnoevil.com';
$apiPort = 8002;
$alarmEventSummary ='Test Alarm Event Summary';
$alarmEventSeverity = 5;
$alarmEventName = '.1.2.3.4.5.0.1';  // maps to testEventName :)

  $alarm = new Curl();
  // ALWAYS RESET OUR ARRAY BEFORE USING IT
  $alarmInfo=array( "device" => gethostname(), "eventSummary" => "" , "eventName" => "", "eventSeverity" => 0 );
  // Limited changes are allowed for event generation to keep it simple
  $alarmInfo['eventSummary'] = $alarmEventSummary;
  $alarmInfo["eventSeverity"] = $alarmEventSeverity;
  $alarmInfo["eventName"] = $alarmEventName;
  // Set our details here
  $alarm->url = $apiUrl . ":" . $apiPort . "/debug/trap";
  $alarm->method = "post";
  $alarm->data($alarmInfo);
//  $alarm->headers = ("X-Api-Key" = "1234fake5");
//  $alarm->headers = ['X-Api-Key: 1234fake5'];
  $alarm->headers = ["X-Api-Key:  $key"];
  $alarm->send();
  $alarm->close();


echo print_r($alarm,true);





?>
