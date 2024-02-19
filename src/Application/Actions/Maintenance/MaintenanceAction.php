<?php
declare(strict_types=1);

namespace App\Application\Actions\Maintenance;

use App\Application\Actions\Action;
use App\Domain\Maintenance\MaintenanceRepository;
use Psr\Log\LoggerInterface;

abstract class MaintenanceAction extends Action {
    /**
     * @var MaintenanceRepository
     */
    protected $maintenanceRepository;

    /**
     * @param LoggerInterface $logger
     * @param MaintenanceRepository $maintenanceRepository
     */
    public function __construct(LoggerInterface $logger, MaintenanceRepository $maintenanceRepository) {
        parent::__construct($logger);
        $this->maintenanceRepository = $maintenanceRepository;
    }
}
