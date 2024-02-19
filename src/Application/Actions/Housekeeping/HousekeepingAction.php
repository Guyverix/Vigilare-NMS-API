<?php
declare(strict_types=1);

namespace App\Application\Actions\Housekeeping;

use App\Application\Actions\Action;
use App\Domain\Housekeeping\HousekeepingRepository;
use Psr\Log\LoggerInterface;

abstract class HousekeepingAction extends Action
{
  protected $housekeepingRepository;

  public function __construct(LoggerInterface $logger, HousekeepingRepository $housekeepingRepository) {
    parent::__construct($logger);
    $this->housekeepingRepository = $housekeepingRepository;
  }
}
