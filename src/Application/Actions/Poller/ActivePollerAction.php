<?php
declare(strict_types=1);
namespace App\Application\Actions\Poller;

use App\Application\Actions\Poller\PollerAction;
use App\Domain\Poller\Poller;
use App\Domain\Poller\PollerRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ActivePollerAction extends PollerAction {

  protected function action(): Response {
    // ('/poller/{poller}/{state}'
    $jobType=["start", "stop", "status", "iteration", "heartbeat"];
    $filterType=["heartbeat", "list"];
    if (empty($this->args["poller"]) ) { $poller="failure";} else { $poller=$this->resolveArg("poller"); }
    if (empty($this->args["state"]) )  { $state="failure";} else { $state=$this->resolveArg("state"); }

    $poller      = preg_replace('/smart/','', $poller);
    $poller      = preg_replace('/Poller/','', $poller);
    $poller      = strtolower($poller);
    if (in_array("$poller" , $filterType)) {
      $state = $poller;
    }

    $this->logger->info("Poller daemon called.  Attempting " . $state . " via the " . $poller . " poller path.");

//    $iteration=$this->pollerRepository->pollerIteration($poller); // should return array of iteration cycles

//    return $this->respondWithData($poller);

    switch ($state) {
    case 'heartbeat':
      $pollerRes = $this->pollerRepository->pollerHeartbeat();
      break;
    case 'list':
      $pollerRes = $this->pollerRepository->pollerList();
      break;
    case 'status':
      $pollerRes = $this->pollerRepository->pollerStatus($poller);
      break;
    case 'iteration':
      $pollerRes = $this->pollerRepository->pollerIteration($poller);
      break;
    case 'start':
      $pollerRes = $this->pollerRepository->pollerRun($poller);
      break;
    case 'stop':
      $pollerRes = $this->pollerRepository->pollerKill($poller);
      break;
    case 'restart':
      $pollerRes1 = $this->pollerRepository->pollerKill($poller);
      sleep(1);
      $pollerRes2 = $this->pollerRepository->pollerRun($poller);
      $pollerRes = array_merge($pollerRes1, $pollerRes2);
      break;
    default:
      $this->logger->error("Poller API called with unknown argument.", [$state]);
      $pollerRes = "Unknown argument given to route.  Only start, status, stop, iteration, heartbeat, list are supported for all iteration cycles";
      throw new HttpBadRequestException($this->request, $pollerRes);
    }
    return $this->respondWithData($pollerRes);
  }
}
