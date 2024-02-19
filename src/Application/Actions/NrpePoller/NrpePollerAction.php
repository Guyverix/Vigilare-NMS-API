<?php
declare(strict_types=1);

namespace App\Application\Actions\NrpePoller;

use App\Application\Actions\Action;
use App\Domain\NrpePoller\NrpePollerRepository;
use Psr\Log\LoggerInterface;

abstract class NrpePollerAction extends Action
{
  protected $nrpePollerRepository;

  public function __construct(LoggerInterface $logger, NrpePollerRepository $nrpePollerRepository) {
    parent::__construct($logger);
    $this->nrpePollerRepository = $nrpePollerRepository;
  }
}
