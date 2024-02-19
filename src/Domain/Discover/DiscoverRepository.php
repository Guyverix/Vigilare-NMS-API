<?php
declare(strict_types=1);

namespace App\Domain\Discover;

interface DiscoverRepository {
    public function findHostname($arr);          // Attempt to get a hostname from an IP address
    public function findIpAddress($arr);         // Attempt to get an IP address from a FQDN
    public function findSnmp();                  // Find our snmp settings that host responds to
    public function workingSnmp($arr);           // figure out what SNMP values work
    public function updateSnmpSettings($arr);    // set hostAttribute table with SNMP settings


    public function validateSnmpSettings($arr);  // check hostAttribute table for existing settings
    public function updateHostGroup($arr);       // set default host group from discovered settings
    public function updateMonitorGroup($arr);    // set default monitor group from discovered settings
    public function setDefaults();               // Set initial database defaults that can be overwritten later
    public function updateDefaults($arr);        // Set initial database defaults that can be overwritten later

}


/*
  Likely order of operations that this should be called in:

  1) initial setup setDefaults() only needed once
  2) at minimum we need an IP address, and HOPEFULLY a FQDN
    a) find our hostname via DNS
    b) find our IP address if given a hostname
  3) retrieve default snmp info
    - findSnmp
  4) find the snmp values that work for the host
    - workingSnmp
  5) Insert returned information into the hostAttribute table
    - updateSnmpSettings

  FUTURE:
  6) POSSIBLE VALIDATE
    - validateSnmpSettings
  7) Set initial hostgroup
    - updateHostGroup
  8) Set initial monitorgroup
    - updateMonitorGroup

  SOONISH:
  1) if we set defaults, we need to be able to change them
    - updateDefaults

*/
