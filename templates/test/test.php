<?php

/*
  The hostname MIGHT be empty if someone screws up, but I am not going to try to dig
  into the path to tease it out.  Meh..

  The filter variable is going to be the important part.  That defines the template
  to use.  IE snmp/interfaces/enp2s0_32.rrd, snmp_interfaces_throughput
*/


/*
  Graphite class SHOULD be simple and reliable.
  Likely errs will actually be if someone gets complex in the template without validating
  what they are doing.
*/

class RenderGraphite {
  public $returnArrayValues;

  public function parseRaw($checkType, $checkName, $sourceList, $sourceOptions = null) {
    if ( ! is_array($sourceList)) { $sourceList=json_decode($sourceList,true); }
    // This WILL always be uniform, so use the array provided
    //echo "" . print_r($sourceList,true);  // DEBUG

    $filter=explode('.', $sourceList[0]['id']);
    if (file_exists(__DIR__ . "/graphite/template_" . $checkType . "_" . $checkName . ".php")) {
      require __DIR__ . "/graphite/template_" . $checkType . "_" . $checkName . ".php";
      return 0;
    }
    elseif (file_exists(__DIR__ . "/graphite/template_" . $checkType . "_default.php")) {
      require __DIR__ . "/graphite/template_" . $checkType . "_default.php";
      return 0;
    }
    else {
      return "Failed to load template file successfully.  Cannot find template file for " . $checkType . " " . $checkName;
    }
  }

  public function graphiteUrls( $checkType, $checkName, $sourceList, $sourceOptions = null) {
    if ( is_null($sourceOptions)) { $sourceOptions = ''; }
    if ( !isset($renderData)) {
      $renderData = new RenderGraphite();
    }
    if( ! is_array($sourceList)) {
      $sourceList = json_decode($sourceList, true);
    }
    //    echo "FUNCTION " . print_r($sourceList, true) . "\n";  // Not in the class, test outside it
    if ( ! is_array($sourceList)) { $sourceList = json_decode($sourceList,true); }
    $returnArray = $renderData->parseRaw($checkType, $checkName, $sourceList, $sourceOptions);
    // A return of 0 is a success
    if ( $returnArray !== 0 ) {
      return "failed to generate graph from parseRaw call for graphiteUrls";
    }

    $returnArray = $renderData->returnArrayValues;
    // print_r($returnArray);  // DEBUG
    if ( ! is_array($returnArray)) {
      return "Template did not return an array " . $returnArray;
    }
    elseif ($returnArray == 1) {
      return "Template exited in an unexpected error somehow.  This one is goofy!";
    }
    else {
      return $returnArray;
    }
  }

}

class RenderRrd {
  public $returnArrayValues;

  public function parseRaw($hostname, $file, $filter, $start, $end, $ignoreMatch = null) {
    if (file_exists(__DIR__ . "/render/". $filter . ".php")) {
      require __DIR__ . "/render/" . $filter . ".php";
      return 0;
    }
    else {
      return "Failed to load template file successfully.  Likely a PHP parsing error.";
    }
  }
}

/*
  This function really needs the filter to be sane.  If something impossible is given
  it is going to choke not finding the file.
*/

function renderGraph($hostname, $file, $filter, $start = null, $end = null, $ignoreMatch = null) {
  if (! isset($renderData)) {
    $renderData = new RenderRrd();
  }
  if ( is_null($start)) { $start='-1d'; }
  if ( is_null($end)) { $end = 'now'; }
  if ( is_null($ignoreMatch)) {
    $ignoreMatch = '';
  }
  elseif( ! is_array($ignoreMatch) ) {
    $ignoreMatch = json_decode($ignoreMatch, true);
  }

  $rendering = $renderData->parseRaw($hostname, $file, $filter, $start, $end, $ignoreMatch);
  // A return of 0 is a success
  if ( $rendering !== 0 ) {
    return "failed to generate graph from parseRaw call for filter " . $filter . " details: ". $rendering;
  }

  $returnArray = $renderData->returnArrayValues;
  // print_r($returnArray);  // DEBUG
  if ( ! is_array($returnArray)) {
    return "Template did not return an array " . $returnArray;
  }
  elseif ($returnArray == 1) {
    return "Template exited in an unexpected error somehow.  This one is goofy!";
  }
  else {
    return $returnArray;
  }
}

/*
  This function will be specific to Graphite, since it is just munging URLs
*/
function graphiteUrls( $checkType, $checkName, $sourceList, $sourceOptions = null) {
  if ( is_null($sourceOptions)) { $sourceOptions = ''; }
  if ( !isset($renderData)) {
    $renderData = new RenderGraphite();
  }
  if( ! is_array($sourceList)) {
    $sourceList = json_decode($sourceList, true);
  }
  echo "FUNCTION " . print_r($sourceList, true) . "\n";

  $returnArray = $renderData->parseRaw($checkType, $checkName, $sourceList, $sourceOptions);
  // A return of 0 is a success
  if ( $returnArray !== 0 ) {
    return "failed to generate graph from parseRaw call for graphiteUrls";
  }

  $returnArray = $renderData->returnArrayValues;
  // print_r($returnArray);  // DEBUG
  if ( ! is_array($returnArray)) {
    return "Template did not return an array " . $returnArray;
  }
  elseif ($returnArray == 1) {
    return "Template exited in an unexpected error somehow.  This one is goofy!";
  }
  else {
    return $returnArray;
  }
}

$checkType = 'nrpe';
$checkName = 'check_memory';
$sourceList='[{"text":"CACHES","id":"nms.guyver-myth_iwillfearnoevil_com.nrpe.check_memory.CACHES","leaf":"1"},{"text":"FREE","id":"nms.guyver-myth_iwillfearnoevil_com.nrpe.check_memory.FREE","leaf":"1"},{"text":"TOTAL","id":"nms.guyver-myth_iwillfearnoevil_com.nrpe.check_memory.TOTAL","leaf":"1"},{"text":"USED","id":"nms.guyver-myth_iwillfearnoevil_com.nrpe.check_memory.USED","leaf":"1"}]';

var_dump(graphiteUrls($checkType, $checkName, $sourceList, null));

// ********* Graphite testing below here ************
/*
$checkType='snmp';
$checkName='hrStorageEntry';
$sourceList='[{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_Ubuntu-Server-20-04-LTS-amd64.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_Ubuntu-Server-20-04-LTS-amd64.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Cached-memory.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Cached-memory.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Memory-buffers.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Memory-buffers.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Physical-memory.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Physical-memory.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Shared-memory.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Shared-memory.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Swap-space.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Swap-space.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Virtual-memory.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-Virtual-memory.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_boot_efi.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_boot_efi.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_dev_shm.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_dev_shm.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_160_ssd.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_160_ssd.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_Calibre.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_Calibre.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_Downloads.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_Downloads.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_Misc.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_Misc.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_Wallpapers.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_Wallpapers.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_fun.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_fun.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_nmsGui.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_nmsGui.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_pi.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_home_chubbard_pi.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_Downloads.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_Downloads.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_MAME.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_MAME.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_1914-9127.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_1914-9127.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_3030-3030.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_3030-3030.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_33cf5a6f-e304-45ad-b730-e0e715533df9.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_33cf5a6f-e304-45ad-b730-e0e715533df9.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_3434-3761.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_3434-3761.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_7F75-417B.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_7F75-417B.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_9016-4EF8.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_9016-4EF8.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_boot.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_boot.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_rootfs.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_rootfs.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_writable.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_media_chubbard_writable.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_mnt_nas02_Backups.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_mnt_nas02_Backups.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_mnt_nas02_Videos.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_mnt_nas02_Videos.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_mnt_nas03_chubbard.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_mnt_nas03_chubbard.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_mnt_nas03_pmorris.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_mnt_nas03_pmorris.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_mnt_nas_video.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_mnt_nas_video.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_run.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_run.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_run_lock.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_run_lock.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_run_qemu.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_run_qemu.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_run_user_1000.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_run_user_1000.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_sys_fs_cgroup.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_sys_fs_cgroup.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_tmp_new_ras.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_tmp_new_ras.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_tmp_ras.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_tmp_ras.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_tmp_ras_new.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_tmp_ras_new.hrStorageUsed","leaf":"1"},{"text":"hrStorageSize","id":"nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_tmp_raspbian.hrStorageSize","leaf":"1"},{"text":"hrStorageUsed","id":" nms.guyver-office_iwillfearnoevil_com.snmp.drive.-_tmp_raspbian.hrStorageUsed","leaf":"1"}]';
*/
/*
$checkType='nrpe';
//$checkName='checkPortLocal-22';
$checkName='checkOpenFiles';
//$sourceList='[{"text":"load1","id":"nms.guyver-office_iwillfearnoevil_com.nrpe.check_load.load1","allowChildren": 0,"expandable": 0,"leaf": 1},{"text":"load5","id":"nms.guyver-office_iwillfearnoevil_com.nrpe.check_load.load5","allowChildren": 0,"expandable": 0,"leaf": 1},{"text":"load15","id":"nms.guyver-office_iwillfearnoevil_com.nrpe.check_load.load15","allowChildren": 0,"expandable": 0,"leaf": 1}]';
$sourceList="[{\"text\":\"open_fd\",\"id\":\"nms.guyver-office_iwillfearnoevil_com.nrpe.checkOpenFiles.open_fd\",\"leaf\":\"1\"}]";

var_dump(graphiteUrls($checkType, $checkName, $sourceList, null));
*/


// *******  RRD testing below here ******************

/*
// Testing drive_space
$hostname='guyver-myth.iwillfearnoevil.com';
$file='/opt/nmsApi/rrd/guyver-myth.iwillfearnoevil.com/snmp/drive/space/_mnt_nas02_Videos_32.rrd';
$filter='snmp_drive_space';
$start='-2d';
$end='now';
var_dump(renderGraph($hostname, $file, $filter, $start, $end));
*/

/*
// Testing drive_statistics
$hostname='guyver-myth.iwillfearnoevil.com';
$file='/opt/nmsApi/rrd/guyver-myth.iwillfearnoevil.com/snmp/drive/statistics/sdb1_32.rrd';
$filter='snmp_drive_statistics';
$start='-4d';
$end='now';
var_dump(renderGraph($hostname, $file, $filter, $start, $end));
*/

/*
// Testing interface rendering
$hostname='guyver-office.iwillfearnoevil.com';
$file='/opt/nmsApi/rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/Realtek_Semiconductor_Co___Ltd__RTL8111_8168_8411_PCI_Express_Gigabit_Ethernet_Controller_32.rrd';
//$file='/opt/nmsApi/rrd/guyver-office.iwillfearnoevil.com/snmp/interfaces/Realtek_Semiconductor_Co___Ltd__RTL8111_8168_8411_PCI_Express_Gigabit_Ethernet_Controller_32.rrd';
$filter='snmp_interfaces_throughput';
$start='-2d';
$end='now';
$ignoreMatch=["lo", "vmnet", "veth666"];
var_dump(renderGraph($hostname, $file, $filter, $start, $end, $ignoreMatch));
*/

/*
// Testing lm-sensors
$file='/opt/nmsApi/rrd/guyver-office.iwillfearnoevil.com/snmp/lm-sensors/volt.plus3_3V_32.rrd';
$hostname='guyver-office.iwillfearnoevil.com';
$filter='snmp_lm-sensors_volt';
$start='-2h';
$end='now';
$ignoreMatch=["lo", "vmnet", "veth"];
var_dump(renderGraph($hostname, $file, $filter, $start, $end, $ignoreMatch));
*/
?>
