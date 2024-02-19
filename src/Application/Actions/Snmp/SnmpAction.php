<?php
declare(strict_types=1);

namespace App\Application\Actions\Snmp;

use App\Application\Actions\Action;
use App\Domain\Snmp\SnmpRepository;
use Psr\Log\LoggerInterface;

abstract class SnmpAction extends Action
{
    /**
     * @var SnmpRepository
     */
    protected $snmpRepository;

    /**
     * @param LoggerInterface $logger
     * @param SnmpRepository $snmpRepository
     */
    public function __construct(LoggerInterface $logger, SnmpRepository $snmpRepository) {
        parent::__construct($logger);
        $this->snmpRepository = $snmpRepository;
    }
}
