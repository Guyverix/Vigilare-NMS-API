<?php
declare(strict_types=1);

namespace App\Application\Actions\Admin;

// Use this when bypassing builtin logging
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;


// Default values (mostly)
use App\Application\Actions\Action;
use App\Domain\Admin\AdminRepository;

// Grab our secrets and the GUI URL (for email links)
use Psr\Container\ContainerInterface;

// Likey not needed
use Psr\Log\LoggerInterface;

abstract class AdminAction extends Action {
    /**
     * @var AdminRepository
     */
    protected $adminRepository;

    /**
     * @var pepper
     */
    protected $pepper;

    protected $frontendUrl;

    /**
     * @param LoggerInterface $logger
     * @param AdminRepository  $adminRepository
     * @param passwordSalt    $c
     */
    public function __construct(AdminRepository $adminRepository, ContainerInterface $c) {
      $logger = new Logger('Admin');
      $processor = new UidProcessor();
      $logger->pushProcessor($processor);
      $handler = new StreamHandler(__DIR__ . '/../../../../logs/adminLevelChanges.log', 'DEBUG');
      $logger->pushHandler($handler);
      $this->logger = $logger;

      $this->adminRepository = $adminRepository;
      $this->pepper = (string)$c->get('passwordPepper');
      $this->frontendUrl = (string)$c->get('frontendUrl');
    }
}
