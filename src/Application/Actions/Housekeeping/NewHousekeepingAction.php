<?php
declare(strict_types=1);
namespace App\Application\Actions\Housekeeping;

use App\Application\Actions\Housekeeping\HousekeepingAction;

use App\Domain\Housekeeping\Housekeeping;
use App\Domain\Housekeeping\HousekeepingRepository;

use Psr\Http\Message\ResponseInterface as Response;

class NewHousekeepingAction extends HousekeepingAction {

  protected function action(): Response {
  $this->logger->info("Housekeeping daemon called.  Attempting to start or status Housekeeping", ["start"]);
  $housekeeping = $this->housekeepingRepository->statusPid();
  return $this->respondWithData("Housekeeping called ok.  Testing return " . $housekeeping );
  }
}
