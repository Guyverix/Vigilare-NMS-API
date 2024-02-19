<?php
declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

abstract class AuthAction extends Action {
    protected $userRepository;
    protected $secret_key;
    protected $pepper;

    public function __construct(LoggerInterface $logger, UserRepository $userRepository, ContainerInterface $c) {
        parent::__construct($logger);
        $this->userRepository = $userRepository;
        $this->secret_key = (string)$c->get('secret_key');
        $this->jwt_expire = (string)$c->get('jwt_expire');
        $this->pepper = (string)$c->get('passwordPepper');

    }
}
