<?php
declare(strict_types=1);

namespace App\Application\Actions\TrapMapping;

use App\Application\Actions\Action;
use App\Domain\TrapMapping\TrapMappingRepository;
use Psr\Log\LoggerInterface;

abstract class TrapMappingAction extends Action
{
  protected $trapMappingRepository;

  public function __construct(LoggerInterface $logger, TraptrapMappingRepository $trapMappingRepository) {
    parent::__construct($logger);
    $this->trapMappingRepository = $trapMappingRepository;

  } // end function
} // end class
