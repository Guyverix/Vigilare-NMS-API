<?php
declare(strict_types=1);

namespace App\Application\Actions\Mapping;

use App\Application\Actions\Action;
use App\Domain\Mapping\MappingRepository;
use Psr\Log\LoggerInterface;

abstract class MappingAction extends Action
{
  protected $mappingRepository;

  public function __construct(LoggerInterface $logger, MappingRepository $mappingRepository) {
    parent::__construct($logger);
//    $this->logger->name = "blah";
    $this->mappingRepository = $mappingRepository;
  } // end function
} // end class
