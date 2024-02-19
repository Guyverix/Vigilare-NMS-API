<?php
declare(strict_types=1);

namespace App\Application\Actions\Trap;

use App\Application\Actions\Action;
use App\Domain\Trap\TrapRepository;
use Psr\Log\LoggerInterface;

abstract class TrapAction extends Action
{
  /**
   * @var TrapRepository
  */
  protected $trapRepository;

  /**
   * @param LoggerInterface $logger
   * @param TrapRepository $trapRepository
  */
  public function __construct(LoggerInterface $logger, TrapRepository $trapRepository) {
    parent::__construct($logger);
    $this->trapRepository = $trapRepository;
  }
}
