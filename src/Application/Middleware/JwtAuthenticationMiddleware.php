<?php

/*
  this has been modified to support API keys as well as JWT tokens.
  It should not allow a bypass of auth, either one or the other condition
  should be met and validated.  Good key or good token, or get stuffed.
*/

namespace App\Application\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpUnauthorizedException;
use Tuupola\Middleware\JwtAuthentication;


// https://github.com/tuupola/slim-jwt-auth
class JwtAuthenticationMiddleware implements Middleware {
    /**
     * @var string
     */
    private $secret_key;
    private $api_auth_keys;
    private $jwt_secure;
    /**
     * JwtAuthenticationMiddleware constructor.
     * @param string $secret_key
     */
    public function __construct(string $secret_key, array $api_auth_keys, string $jwt_secure) {
        // sourced values from the dependencies.php injection from settings.php
        $this->secret_key = $secret_key;
        $this->api_auth_keys = $api_auth_keys;
        $this->jwt_secure = $jwt_secure;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface {
        $header = $request->getHeaders();
        if (isset($header["X-Api-Key"])) {                   // check if we have an API key.  If so validate it.
          $sentApiKey = $header['X-Api-Key'];
          $sentApiKey = $sentApiKey[0];
          if (in_array($sentApiKey, $this->api_auth_keys)) { // compare string from X-Api-Key to our array for auth
            return $handler->handle($request);
          }
          throw new HttpUnauthorizedException($request, "Invalid API key given");
        }
/*
        // Works but not actually needed, as JWT has native cookie support from tuupola
        elseif (isset($_COOKIE['Authentication'])) {         // Support keeping the JWT in a cookie
          $ja2 = new JwtAuthentication([
            "secret" => $this->secret_key,
            "secure" => $this->jwt_secure,
            "algorithm" => ["HS256"],
            "attribute" => false,
            "error" => function (ResponseInterface $response, $arguments) use ($request) {
                throw new HttpUnauthorizedException($request, $arguments["message"]);
            },
            "before" => function (Request $request, $arguments) {
                $token = $arguments["decoded"];
                $data = $token["data"] ?? false;
               $request = $request->withAttribute("user", $data);
                return $request;
            }
          ]);
          return $ja2->process($request, $handler);
        }
*/
        else {                                               // we were not given an API key, so switch to JWT now + cookie support
          /* origional working code */
          $ja = new JwtAuthentication([
            "secret" => $this->secret_key,
            "secure" => $this->jwt_secure,
            "algorithm" => ["HS256"],
            "attribute" => false,
            "cookie" => 'Authentication',
            "error" => function (ResponseInterface $response, $arguments) use ($request) {
                throw new HttpUnauthorizedException($request, $arguments["message"]);
            },
            "before" => function (Request $request, $arguments) {
                $token = $arguments["decoded"];
                $data = $token["data"] ?? false;
                $request = $request->withAttribute("user", $data);
                return $request;
            }
          ]);
          return $ja->process($request, $handler);
        }
    }
}
