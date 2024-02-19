<?php
declare(strict_types=1);
namespace App\Application\Actions\Device;

use App\Application\Actions\Action;
use App\Domain\Device\DeviceRepository;
use Psr\Log\LoggerInterface;

abstract class DeviceAction extends Action {

  protected $deviceRepository;

  public function __construct(LoggerInterface $logger, DeviceRepository $deviceRepository) {
    parent::__construct($logger);
    $this->deviceRepository = $deviceRepository;
  } // end function
} // end class
