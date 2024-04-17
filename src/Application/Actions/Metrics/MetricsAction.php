<?php
declare(strict_types=1);
namespace App\Application\Actions\Metrics;

use App\Application\Actions\Action;
use App\Domain\Metrics\MetricsRepository;
use Psr\Log\LoggerInterface;

abstract class MetricsAction extends Action {

  protected $metricsRepository;

  public function __construct(LoggerInterface $logger, MetricsRepository $metricsRepository) {
    parent::__construct($logger);
    $this->metricsRepository = $metricsRepository;
  } // end function
} // end class
