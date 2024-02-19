<?php

/*
  this template is specific to the WD home NAS devices
  https://community.home-assistant.io/t/wd-ex4100-nas-snmp-sensors/246020
  WD OID 1.3.6.1.4.1.5127.1.1.1.10.1
  This device is kind of weak for SNMP responses.  It appears
  to timeout easily.

  Additionally everything is defined as a string value.  So
  No really good counter metrics are available

  this is a good candidate for storing in the database to display
  against a SLOW iteration cycle.

  That being the case, make names pretty and useful for UI
*/

  $dataToBeInserted=json_decode($dataToBeInserted, true);

  /* create an empty array of our index values */
  $listVol=array();
  $listDrive=array();
  $settings=array();
  $settings['source']='template_wdNasData.php';
  foreach ($dataToBeInserted as $k => $v) {
    if ( strpos($k, '3.6.1.4.1.5127.1.1.1.10.1.9.1.1.') !==false) {
      $volumeIndexNumber=ltrim(rtrim($v));
      $listVol[]=$volumeIndexNumber;
    }
    elseif ( strpos($k, '.3.6.1.4.1.5127.1.1.1.10.1.10.1.1.') !==false) {
      $driveIndexNumber=ltrim(rtrim($v));
      $listDrive[]=$driveIndexNumber;
    } // end if
  }  // end foreach

  // Now begin building out the return data in a readable way
  foreach ($dataToBeInserted as $k => $v) {
    switch ($k) {
      case ( strpos($k, '5127.1.1.1.10.1.2.') !==false):
        $settings['Software Version']=$v;
        break;
      case ( strpos($k,'5127.1.1.1.10.1.3.' ) !==false):
        $settings['Device Name']=$v;
        break;
      case ( strpos($k,'5127.1.1.1.10.1.7.0' ) !==false):
        $settings['Internal Temp']=$v;
        break;
      case ( strpos($k,'5127.1.1.1.10.1.8.0' ) !==false):
        $settings['Fan State']=$v;
        break;
    }
  }

  foreach ($listVol as $volNumber) {
    foreach ($dataToBeInserted as $k => $v) {
      switch ($K) {
        case (strpos($k, "3.6.1.4.1.5127.1.1.1.10.1.9.1.2.$volNumber") !== false):
          $settings['Volume'][$volNumber]['Volume Name']=$v;
          break;
        case (strpos($k, "3.6.1.4.1.5127.1.1.1.10.1.9.1.3.$volNumber") !== false):
          $settings['Volume'][$volNumber]['Volume Format']=$v;
          break;
        case (strpos($k, "3.6.1.4.1.5127.1.1.1.10.1.9.1.4.$volNumber") !== false):
          $settings['Volume'][$volNumber]['RAID Type']=$v;
          break;
        case (strpos($k, "3.6.1.4.1.5127.1.1.1.10.1.9.1.5.$volNumber") !== false):
          $settings['Volume'][$volNumber]['Total Storage']=$v;
          break;
        case (strpos($k, "3.6.1.4.1.5127.1.1.1.10.1.9.1.6.$volNumber") !== false):
          $settings['Volume'][$volNumber]['Available Storage']=$v;
          break;
      }
    }
  }

  foreach ($listDrive as $driveNumber) {
    foreach ($dataToBeInserted as $k => $v) {
      switch ($K) {
        case (strpos($k, "3.6.1.4.1.5127.1.1.1.10.1.10.1.2.$driveNumber") !== false):
          $settings['Drive'][$driveNumber]['Manufacturer']=$v;
          break;
        case (strpos($k, "3.6.1.4.1.5127.1.1.1.10.1.10.1.3.$driveNumber") !== false):
          $settings['Drive'][$driveNumber]['Serial Number']=$v;
          break;
        case (strpos($k, "3.6.1.4.1.5127.1.1.1.10.1.10.1.4.$driveNumber") !== false):
          $settings['Drive'][$driveNumber]['Secondary Number']=$v;
          break;
        case (strpos($k, "3.6.1.4.1.5127.1.1.1.10.1.10.1.5.$driveNumber") !== false):
          $settings['Drive'][$driveNumber]['Drive Temp']=$v;
          break;
        case (strpos($k, "3.6.1.4.1.5127.1.1.1.10.1.10.1.2.$driveNumber") !== false):
          $settings['Drive'][$driveNumber]['Capacity']=$v;
          break;
      }
    }
  }
  $this->returnArrayValues=$settings;
  return $this->returnArrayValues;

?>
