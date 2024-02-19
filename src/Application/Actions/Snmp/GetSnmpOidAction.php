<?php
declare(strict_types=1);

namespace App\Application\Actions\Snmp;

use App\Application\Validation\Snmp\SnmpOidValidator;
use App\Application\Actions\Snmp\SnmpAction;
use Psr\Http\Message\ResponseInterface as Response;


/* This is specific to ONE single oid being returned, NEVER a table */
class GetSnmpOidAction extends SnmpAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $snmpRequest1 = $this->getFormData();
        /* hostname and oid cannot be guessed, duh */
        if (! isset($snmpRequest1['community']) ) { $snmpRequest1['community']='public'; }
        if (! isset($snmpRequest1['version'])   ) { $snmpRequest1['version']='2'; }
        /* Add more data from $_SERVER for logging */
        $snmpRequest = array_merge($snmpRequest1, $_SERVER);

        $validator = new SnmpOidValidator();
        $validator->validate($snmpRequest);

        if ( $snmpRequest['version'] == '1' ) {
          $snmpResponse = $this->snmpRepository->returnSnmpOid($snmpRequest);
        }
        elseif ( str_contains($snmpRequest['version'],'2') ) {
          $snmpResponse = $this->snmpRepository->returnSnmpOid($snmpRequest);
        }
        elseif ( $snmpRequest['version'] == '3' ) { $snmpResponse=["SNMP v3 is not currently supported.  Yet"]; }
        else { $snmpResponse=["Unkown internal error happened resolving snmp version"]; }

        // $this->logger->info("snmp oid query " . $snmpRequest['oid'] . " was pulled for ".  $snmpRequest['hostname'] . ".");
        $this->logger->info("snmp single oid query run", $snmpRequest);

        return $this->respondWithData($snmpResponse);
    }
}
