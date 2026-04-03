<?php
declare(strict_types=1);

namespace App\Application\Actions\Monitors;

use App\Application\Actions\Action;
use App\Domain\Monitors\MonitorsRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

abstract class MonitorsAction extends Action {
  /**
   * @var MonitorsRepository
   */
  protected $monitorsRepository;
  protected $pollerIpAddress;

  /**
   * @param LoggerInterface $logger
   * @param MonitoringRepositoryRepository $discoverRepository
   */
  public function __construct(LoggerInterface $logger, MonitorsRepository $monitorsRepository, ContainerInterface $c ) {
    parent::__construct($logger);
    $this->monitorsRepository = $monitorsRepository;
    $this->pollerIpAddress = (string)$c->get('pollerIpAddress');
  }
}
