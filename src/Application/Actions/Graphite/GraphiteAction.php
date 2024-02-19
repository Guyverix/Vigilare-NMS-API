<?php
declare(strict_types=1);

namespace App\Application\Actions\Graphite;

use App\Application\Actions\Action;
use App\Domain\Graphite\GraphiteRepository;
use Psr\Log\LoggerInterface;

abstract class GraphiteAction extends Action {
    /**
     * @var GraphiteRepository
     */
    protected $graphiteRepository;

    /**
     * @param LoggerInterface $logger
     * @param GraphiteRepository $graphiteRepository
     */
    public function __construct(LoggerInterface $logger, GraphiteRepository $graphiteRepository ) {
        parent::__construct($logger);
        $this->graphiteRepository = $graphiteRepository;
    }
}
