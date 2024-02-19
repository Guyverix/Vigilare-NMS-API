<?php
declare(strict_types=1);

namespace App\Application\Actions\User;

// Use this when bypassing builtin logging
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;



use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

abstract class UserAction extends Action {
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @var pepper
     */
    protected $pepper;

    protected $frontendUrl;

    /**
     * @param LoggerInterface $logger
     * @param UserRepository  $userRepository
     * @param passwordSalt    $c
     */
//    public function __construct(LoggerInterface $logger, UserRepository $userRepository, ContainerInterface $c) {
    public function __construct(UserRepository $userRepository, ContainerInterface $c) {
      $logger = new Logger('User');
      $processor = new UidProcessor();
      $logger->pushProcessor($processor);
      $handler = new StreamHandler(__DIR__ . '/../../../../logs/authentication.log', 'DEBUG');
      $logger->pushHandler($handler);
      $this->logger = $logger;

      $this->userRepository = $userRepository;
      $this->pepper = (string)$c->get('passwordPepper');
      $this->frontendUrl = (string)$c->get('frontendUrl');
    }
}
