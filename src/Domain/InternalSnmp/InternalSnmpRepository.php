<?php
declare(strict_types=1);

namespace App\Domain\InternalSnmp;

interface InternalSnmpRepository {

    public function returnSnmpOid($arr): array;  // Return OID value from query as an array
    //    public function returnSnmpTable($snmpRequest): array;
    //    public function testSnmp$(snmprequest);


/*  todo:
    public function returnSnmpOidv3($snmpRequest): array;
    public function returnSnmpTablev3($snmpRequest): array;
*/

/*
    Below will be used to update default OIDs with basic maps
    for display, etc
*/
/*
    public function insertOidSearch($value): array;
    public function insertOidTableSearch($value): array;
*/
}
