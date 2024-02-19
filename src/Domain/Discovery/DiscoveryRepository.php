<?php
declare(strict_types=1);

namespace App\Domain\Discovery;

// Set database class here, so we do not bleed into the API any database values
// when errors occur.  No need to "use" it here however.
require __DIR__ . '/../../../app/Database.php';

// CRUD  Create Retrieve Update Delete
interface DiscoveryRepository {

// Create
    public function CreateDiscoveredDevice($arr): array;            // Add hostname, address, productionState into Devices if not existing
    public function CreateDiscoveredDeviceFolder($arr): array;      // Just what it says on the box only if not existing
    public function CreateDiscoveredDeviceProperties($arr): array;  // Add initial DeviceProperties only
    public function CreateDevicePropertiesTemplate($arr): array;    // Will add values into template table
    public function CreateDeviceFolder($arr): array;                // Will a DeviceFolder name to the DeviceFolder table.  devices will be empty by default
    public function CreateDevice($arr): array;                      // Basically a dupe of Device Create path, but meh.. Go for completeness

// Retrieve Information of some kind
    public function FindTemplateDefaultDeviceProperties($arr): array; // returns string from templateValue for Device & A_Default
    public function FindTemplateDeviceProperties($arr): array;        // returns templateValue by name and device folder for filtering
    public function FindDeviceSnmpSettings($arr): array;              // returns JSON of working snmp values or { "snmp": ["version": "none"]}
    public function FindDeviceDetail($arr): array;                    // returns array with hostname and address from Device table




/*

    public function FindAddressDevice($arr): array;                 // returns string known or unknown
    public function FindHostnameDevice($arr): array;                // returns string known or unknown
    public function FindIpAddress($arr): array;                     // Returns IP address via builtin PHP
    public function FindIpAddressDigShort($arr): array;             // Returns IP address via shell dig command
    public function GetIpAddresses($arr): array;                    // Returns array of IP adddresses for a given CIDR
    public function CheckIfKnownAddressesInRange($arr): array;      // returns string valid or invalid
    public function GetAllKnownAddresses();                         // retrun array of all known IP addresses from Device
    public function CheckIfKnownFromScan($arr): array;              // returns string known or unknown from device table checks hostname AND address
    public function IpInNetwork($arr): array;                       // Return True or False if in network or not
    public function NmapPingScan($arr): array;                      // pingscan subnet of IP addresses
    public function NmapDeviceOpenPorts($arr): array;               // Return array of open ports as "6667/open/tcp//irc///"  Explode later to get ports, tcp/udp and names
    public function ping($arr): array;                              // ping a hostname with a SINGLE ping.  Fast but not safe
*/

// These are helpers, and likely will be needed in the future, but
// not right now. (or maybe ever)
/*
    public function returnNew($arr): array;
    public function returnHost($arr): array;
    public function returnMap($arr): array;
    public function returnPreMap($arr): array;
    public function returnPostMap($arr): array;
*/


// Update NOPE!  Discovery just discovers if we need to repair, it
// is no longer a discovery, Duh.
/*
    public function UpdateDiscoveredDevice($arr): array;
    public function UpdateDiscoveredDeviceFolder($arr): array;
    public function UpdateDiscoveredDeviceProperties($arr): array;
*/


// Delete NOPE! Discovery just discovers, it is not going to clobber
// stuff when it finds dupes as it should never attempt to do anything
// when it finds something already existing
/*
    public function DeleteDiscoveredDevice($arr): array;
    public function DeleteDiscoveredDeviceFolder($arr): array;
    public function DeleteDiscoveredDeviceProperties($arr): array;
*/
}
