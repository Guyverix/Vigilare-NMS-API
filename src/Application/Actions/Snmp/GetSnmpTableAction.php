<?php
declare(strict_types=1);

namespace App\Application\Actions\Snmp;

use App\Application\Action;
use App\Application\Validation\Snmp\SnmpOidValidator;
use App\Application\Actions\Snmp\SnmpAction;
use Psr\Http\Message\ResponseInterface as Response;

class GetSnmpTableAction extends SnmpAction
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
        /* if we somehow get a newline in the oid, strip the damn thing */
        $snmpRequest1['oid']=str_replace(array("\n","\r\n","\r"),'',$snmpRequest1['oid']);
        /* TESTING ONLY: if (! isset($snmpRequest['oid'])   )     { $snmpRequest['oid']='1.3.6.1.2.1.1.9.1'; }  */

        /* Add more data from $_SERVER for logging */
        $snmpRequest = array_merge($snmpRequest1, $_SERVER);
        $snmpRequest['httpResponseCode'] = 200; /* assume success until otherwise noted */

        $validator = new SnmpOidValidator();
        $validator->validate($snmpRequest);

        if ( $snmpRequest['version'] == '1' ) {
          $snmpResponse = $this->snmpRepository->returnSnmpTable($snmpRequest);
        }
        elseif ( str_contains($snmpRequest['version'],'2') ) {
          $snmpResponse = $this->snmpRepository->returnSnmpTable($snmpRequest);
        }
        elseif ( $snmpRequest['version'] == '3' ) {
          $snmpResponse=["SNMP v3 is not currently supported.  Yet"];
          $responseCode=501;
          $snmpRequest['httpResponseCode'] = $responseCode;
        }
        else {
          $snmpResponse=["Unkown internal error happened resolving snmp version"];
          $responseCode=400;
          $snmpRequest['httpResponseCode'] = $responseCode;
        }

        //$this->logger->info("snmp table query " . $snmpRequest['oid'] . " was pulled for " . $snmpRequest['hostname'] . ".");
        if ( isset( $responseCode ) ) {
          $this->logger->error("snmp table query run", $snmpRequest);
          return $this->respondWithData($snmpResponse, $responseCode);
        }
        else {
          $this->logger->info("snmp table query run", $snmpRequest);
          return $this->respondWithData($snmpResponse);
        }
    }
}
