<?php
declare(strict_types=1);

namespace App\Application\Actions\Monitors;

use App\Application\Actions\Action;
use App\Domain\Monitors\MonitorsRepository;
use Psr\Log\LoggerInterface;

abstract class MonitorsAction extends Action {
  /**
   * @var MonitorsRepository
   */
  protected $monitorsRepository;

  /**
   * @param LoggerInterface $logger
   * @param MonitoringRepositoryRepository $discoverRepository
   */
  public function __construct(LoggerInterface $logger, MonitorsRepository $monitorsRepository ) {
    parent::__construct($logger);
    $this->monitorsRepository = $monitorsRepository;
  }
}
