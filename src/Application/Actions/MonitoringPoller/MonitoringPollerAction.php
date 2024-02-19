<?php
declare(strict_types=1);

namespace App\Application\Actions\MonitoringPoller;

use App\Application\Actions\Action;
use App\Domain\MonitoringPoller\MonitoringPollerRepository;
use Psr\Log\LoggerInterface;

abstract class MonitoringPollerAction extends Action {
  /**
   * @var MonitoringPollerRepository
   */
  protected $monitoringPollerRepository;

  /**
   * @param LoggerInterface $logger
   * @param MonitoringRepositoryRepository $discoverRepository
   */
  public function __construct(LoggerInterface $logger, MonitoringPollerRepository $monitoringPollerRepository ) {
    parent::__construct($logger);
    $this->monitoringPollerRepository = $monitoringPollerRepository;
  }
}
