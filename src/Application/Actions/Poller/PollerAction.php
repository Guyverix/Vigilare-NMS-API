<?php
declare(strict_types=1);

namespace App\Application\Actions\Poller;

use App\Application\Actions\Action;
use App\Domain\Poller\PollerRepository;
use Psr\Log\LoggerInterface;

abstract class PollerAction extends Action
{
  protected $pollerRepository;

  public function __construct(LoggerInterface $logger, PollerRepository $pollerRepository) {
    parent::__construct($logger);
    $this->pollerRepository = $pollerRepository;
  }
}
