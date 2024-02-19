<?php
declare(strict_types=1);

namespace App\Application\Actions\GlobalMapping;

use App\Application\Actions\Action;
use App\Domain\GlobalMapping\GlobalMappingRepository;
use Psr\Log\LoggerInterface;

abstract class GlobalMappingAction extends Action {
  protected $globalmappingRepository;

  public function __construct(LoggerInterface $logger, GlobalMappingRepository $globalmappingRepository) {
    parent::__construct($logger);
    $this->logger = $logger;
    $this->globalmappingRepository = $globalmappingRepository;
  } // end function
} // end class
