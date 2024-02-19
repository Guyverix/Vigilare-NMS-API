<?php
declare(strict_types=1);
namespace App\Application\Actions\ShellPoller;

use App\Application\Actions\ShellPoller\ShellPollerAction;

use App\Domain\ShellPoller\ShellPoller;
use App\Domain\ShellPoller\ShellPollerRepository;

use Psr\Http\Message\ResponseInterface as Response;

class ActiveShellPollerAction extends ShellPollerAction {

  protected function action(): Response {
    $this->logger->info("Shell Poller daemon called.  Attempting to start or status poller", ["start"]);
    $responseCode=200;
    $state=$this->resolveArg('state');
    $iteration=$this->shellPollerRepository->pollerIteration(); // should return array of iteration cycles

    if ($state == 'status') {
      $poller = $this->shellPollerRepository->pollerStatus();
    }
    elseif ($state == 'iteration') {
      $poller = $this->shellPollerRepository->pollerIteration();
    }
    elseif ($state == 'start') {
//      $poller = $this->shellPollerRepository->pollerRun($iteration);
      $poller = $this->shellPollerRepository->pollerRun();
    }
    elseif ($state == 'stop') {
      $poller = $this->shellPollerRepository->pollerKill();
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
