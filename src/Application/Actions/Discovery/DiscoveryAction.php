<?php
declare(strict_types=1);

namespace App\Application\Actions\Discovery;

use App\Application\Actions\Action;
use App\Domain\Discovery\DiscoveryRepository;
use Psr\Log\LoggerInterface;

abstract class DiscoveryAction extends Action {
  /**
   * @var DiscoveryRepository
   */
  protected $discoveryRepository;

  /**
   * @param LoggerInterface $logger
   * @param DiscoverRepository $discoverRepository
   */
  public function __construct(LoggerInterface $logger, DiscoveryRepository $discoveryRepository ) {
    parent::__construct($logger);
    $this->discoveryRepository = $discoveryRepository;
  }
}
