<?php
declare(strict_types=1);
namespace App\Application\Actions\Housekeeping;

use App\Application\Actions\Housekeeping\HousekeepingAction;
use App\Domain\Housekeeping\HousekeepingRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ActiveHousekeepingAction extends HousekeepingAction {
  protected function action(): Response {
    $responseCode=200;
    $state=$this->resolveArg('state');

    if ($state == 'status') {
      $housekeeping = $this->housekeepingRepository->housekeepingStatus();
    }
    elseif ($state == 'iteration') {
      $housekeeping = $this->housekeepingRepository->housekeepingIteration();
    }
    elseif ($state == 'known') {
      $housekeeping = $this->housekeepingRepository->housekeepingKnown();
    }
    elseif ($state == 'start') {
      $housekeeping = $this->housekeepingRepository->housekeepingRun();
    }
    elseif ($state == 'stop') {
      $housekeeping = $this->housekeepingRepository->housekeepingKill();
    }
    elseif ($state == 'cleanPerformanceTable') {
      $housekeeping = $this->housekeepingRepository->cleanPerformanceTable(30);
    }
    else {
      $this->logger->error("Housekeeping daemon called with unknown argument.", ["unknown args"]);
      $housekeeping = "Unknown argument given to route.  Only start, status, stop, iteration are supported for all iteration cycles";
      $responseCode=500;
    }
  //  $housekeeping = json_encode($housekeeping);
  //  return $this->respondWithData("Housekeeping called ok.  Testing return " . $housekeeping );
  $this->logger->info("Housekeeping UI called.", ["$state"]);
  return $this->respondWithData($housekeeping);
  }
}
