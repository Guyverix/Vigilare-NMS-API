<?php
declare(strict_types=1);

namespace App\Application\Actions\ShellPoller;

use App\Application\Actions\Action;
use App\Domain\ShellPoller\ShellPollerRepository;
use Psr\Log\LoggerInterface;

abstract class ShellPollerAction extends Action
{
  protected $shellPollerRepository;

  public function __construct(LoggerInterface $logger, ShellPollerRepository $shellPollerRepository) {
    parent::__construct($logger);
    $this->shellPollerRepository = $shellPollerRepository;
  }
}
