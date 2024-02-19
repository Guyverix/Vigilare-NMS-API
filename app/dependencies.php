<?php
declare(strict_types=1);

/*
  This is what takes values from the settings.php file and makes
  them available for other classes and functions to use in the
  constructor for the class requesting the variable.
*/


use App\Application\Middleware\JwtAuthenticationMiddleware;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpUnauthorizedException;            // unneeded?
use Tuupola\Middleware\JwtAuthentication;                // unneeded?

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get('settings');

            $loggerSettings = $settings['logger'];
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        JwtAuthenticationMiddleware::class => function (ContainerInterface $c): JwtAuthenticationMiddleware {
            $secret_key = (string)$c->get('secret_key');
            $jwt_secure = (string)$c->get('jwt_secure');
            $api_auth_keys = (array)$c->get('api_auth_keys');
            return new JwtAuthenticationMiddleware($secret_key , $api_auth_keys, $jwt_secure);
        },
        ApiKeyMiddleware::class => function (ContainerInterface $c): ApiKeyMiddleware {
            $api_auth_keys = (array)$c->get('api_auth_keys');
            return new ApiKeyMiddleware($api_auth_keys);
        }
    ]);
};
