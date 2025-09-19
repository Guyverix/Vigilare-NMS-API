<?php
declare(strict_types=1);

namespace App\Application\Actions\TEMPLATE;

use App\Application\Actions\Action;
use App\Domain\TEMPLATE\TEMPLATERepository;
use Psr\Log\LoggerInterface;

abstract class TEMPLATEAction extends Action {
    /**
     * @var TEMPLATERepository
     */
    protected $TEMPLATERepository;

    /**
     * @param LoggerInterface $logger
     * @param TEMPLATERepository $templateRepository
     */
    public function __construct(LoggerInterface $logger, TEMPLATERepository $templateRepository) {
        parent::__construct($logger);
        $this->templateRepository = $templateRepository;
    }
}
