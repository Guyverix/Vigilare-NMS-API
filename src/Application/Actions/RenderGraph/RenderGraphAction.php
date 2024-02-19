<?php
declare(strict_types=1);

namespace App\Application\Actions\RenderGraph;

use App\Application\Actions\Action;
use App\Domain\RenderGraph\RenderGraphRepository;
use Psr\Log\LoggerInterface;

abstract class RenderGraphAction extends Action {
  /**
   * @var RenderGraphRepository
   */
  protected $renderGraphRepository;

  /**
   * @param LoggerInterface $logger
   * @param MonitoringRepositoryRepository $discoverRepository
   */
  public function __construct(LoggerInterface $logger, RenderGraphRepository $renderGraphRepository ) {
    parent::__construct($logger);
    $this->renderGraphRepository = $renderGraphRepository;
  }
}
