<?php
declare(strict_types=1);
namespace App\Application\Actions\Poller;

use App\Application\Actions\Poller\PollerAction;

use App\Domain\Poller\Poller;
use App\Domain\Poller\PollerRepository;

use Psr\Http\Message\ResponseInterface as Response;

class NewPollerAction extends PollerAction {

  protected function action(): Response {
  $this->logger->info("Poller daemon called.  Attempting to start or status poller", ["start"]);
  $poller = $this->pollerRepository->statusPid();
  return $this->respondWithData("Poller called ok.  Testing return " . $poller );
  }
}
