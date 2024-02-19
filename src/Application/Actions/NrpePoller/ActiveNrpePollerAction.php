<?php
declare(strict_types=1);
namespace App\Application\Actions\NrpePoller;

use App\Application\Actions\NrpePoller\NrpePollerAction;

use App\Domain\NrpePoller\NrpePoller;
use App\Domain\NrpePoller\NrpePollerRepository;

use Psr\Http\Message\ResponseInterface as Response;

class ActiveNrpePollerAction extends NrpePollerAction {

  protected function action(): Response {
    $this->logger->info("Nrpe Poller daemon called.  Attempting to start or status poller", ["start"]);
    $responseCode=200;
    $state=$this->resolveArg('state');
    $iteration=$this->nrpePollerRepository->pollerIteration(); // should return array of iteration cycles

    if ($state == 'status') {
      $poller = $this->nrpePollerRepository->pollerStatus();
    }
    elseif ($state == 'iteration') {
      $poller = $this->nrpePollerRepository->pollerIteration();
    }
    elseif ($state == 'start') {
      $poller = $this->nrpePollerRepository->pollerRun();
    }
    elseif ($state == 'stop') {
      $poller = $this->nrpePollerRepository->pollerKill();
    }
    else {
      $this->logger->error("Poller daemon called with unknown argument.", ["unknown args"]);
      $poller = "Unknown argument given to route.  Only start, status, stop, iteration are supported for all iteration cycles";
      $responseCode=500;
    }
//  $poller = json_encode($poller);
//  return $this->respondWithData("Poller called ok.  Testing return " . $poller );
  return $this->respondWithData($poller);
  }
}
