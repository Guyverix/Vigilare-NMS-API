<?php
declare(strict_types=1);

namespace App\Application\Actions\History;

use App\Application\Actions\Action;
use App\Domain\History\HistoryRepository;
use Psr\Log\LoggerInterface;

abstract class HistoryAction extends Action
{
    /**
     * @var HistoryRepository
     */
    protected $historyRepository;

    /**
     * @param LoggerInterface $logger
     * @param HistoryRepository $historyRepository
     */
    public function __construct(LoggerInterface $logger, HistoryRepository $historyRepository) {
        parent::__construct($logger);
        $this->historyRepository = $historyRepository;
    }
}
