<?php
declare(strict_types=1);
namespace App\Application\Actions\NrpePoller;

use App\Application\Actions\NrpePoller\NrpePollerAction;

use App\Domain\NrpePoller\NrpePoller;
use App\Domain\NrpePoller\NrpePollerRepository;

use Psr\Http\Message\ResponseInterface as Response;

class NewNrpePollerAction extends NrpePollerAction {

  protected function action(): Response {
  $this->logger->info("Nrpe-Poller daemon called.  Attempting to start or status Nrpe-Poller daemon", ["start"]);
  $NrpePoller = $this->nrpePollerRepository->statusPid();
  return $this->respondWithData("Nrpe-Poller called ok.  Testing return " . $NrpePoller );
  }
}
