<?php
declare(strict_types=1);

namespace App\Application\Actions\Reporting;

use App\Application\Actions\Action;
use App\Domain\Reporting\ReportingRepository;
use Psr\Log\LoggerInterface;

abstract class ReportingAction extends Action {
  protected $reportingRepository;

  public function __construct(LoggerInterface $logger, ReportingRepository $reportingRepository) {
    parent::__construct($logger);
    $this->reportingRepository = $reportingRepository;
  }
}
