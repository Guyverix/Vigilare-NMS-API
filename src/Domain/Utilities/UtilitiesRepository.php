<?php
declare(strict_types=1);

namespace App\Domain\Utilities;

// Set database class here, so we do not bleed into the API any database values
// when errors occur.  No need to "use" it here however.
require __DIR__ . '/../../../app/Database.php';

// CRUD  Create Retrieve Update Delete
interface UtilitiesRepository {

// Create (implies install as well)
/*
    In general Utilities should not create things, unless there is a damn good reason
    such as the initial installation of the application.
    public function InstallSoftware($arr): array;
    public function InstallVendorSoftware($arr): array;
    public function CreateSomeEvilThing($arr): array;
*/

// Retrieve Information of some kind.  This is likely going to get VERY large going forward
    public function FindAddressDevice($arr): array;                 // returns string known or unknown
    public function FindHostnameDevice($arr): array;                // returns string known or unknown
    public function FindIpAddress($arr): array;                     // Returns IP address via builtin PHP
    public function FindIpAddressDigShort($arr): array;             // Returns IP address via shell dig command
    public function FindIpAddressDigTrace($arr): array;             // Returns DNS trace for FQDN
    public function FindIpAddressDigAny($arr): array;               // Returns Any IP values for a given FQDN
    public function GetIpAddresses($arr): array;                    // Returns array of IP adddresses for a given CIDR
    public function CheckIfKnownAddressesInRange($arr): array;      // returns string valid or invalid
    public function GetAllKnownAddresses();                         // retrun array of all known IP addresses from Device
    public function GetAllKnownHostnames();                         // retrun array of all known hostnames from Device
    public function CheckIfKnownFromScan($arr): array;              // returns string known or unknown from device table checks hostname AND address
    public function IpInNetwork($arr): array;                       // Return True or False if in network or not
    public function NmapPingScan($arr): array;                      // pingscan subnet of IP addresses
    public function NmapDeviceOpenPorts($arr): array;               // Return array of open ports as "6667/open/tcp//irc///"  Explode later to get ports, tcp/udp and names
    public function ping($arr): array;                              // ping a hostname with a SINGLE ping.  Fast but not safe

/*
    These helpers likely will be needed in the future, but
    not right now. (or maybe ever)

    public function returnNew($arr): array;
    public function returnHost($arr): array;
    public function returnMap($arr): array;
    public function returnPreMap($arr): array;
    public function returnPostMap($arr): array;
*/


// Update
/*
    Initially Utilities are designed to retrieve information for other API's to be able
    to consume.  There may be times where it is appropriate for a utility function to
    update something in the database, but that is far future I hope.
    public function UpdateSomeEvilThing($arr): array;
*/


// Delete
/*
    This I hope will never have to be defined.  Having a utility deleting stuff kinda implies
    that there are bad bugs in the code and this is needed to clean up a mess I created.
    Hopefully it never gets to this point.
    public function DeleteSomeFuckingBuggyData($arr): array;
*/

}
