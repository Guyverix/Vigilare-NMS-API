<?php
declare(strict_types=1);
namespace App\Application\Actions\ShellPoller;

use App\Application\Actions\ShellPoller\ShellPollerAction;

use App\Domain\ShellPoller\ShellPoller;
use App\Domain\ShellPoller\ShellPollerRepository;

use Psr\Http\Message\ResponseInterface as Response;

class NewShellPollerAction extends ShellPollerAction {

  protected function action(): Response {
  $this->logger->info("Shell-Poller daemon called.  Attempting to start or status Shell-Poller daemon", ["start"]);
  $ShellPoller = $this->shellPollerRepository->statusPid();
  return $this->respondWithData("Shell-Poller called ok.  Testing return " . $ShellPoller );
  }
}
