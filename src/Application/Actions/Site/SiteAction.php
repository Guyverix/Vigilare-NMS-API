<?php
declare(strict_types=1);

namespace App\Application\Actions\Site;

use App\Application\Actions\Action;
use App\Domain\Site\SiteRepository;
use Psr\Log\LoggerInterface;

abstract class SiteAction extends Action {
    /**
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @param LoggerInterface $logger
     * @param SiteRepository $siteRepository
     */
    public function __construct(LoggerInterface $logger, SiteRepository $siteRepository) {
        parent::__construct($logger);
        $this->siteRepository = $siteRepository;
    }
}
