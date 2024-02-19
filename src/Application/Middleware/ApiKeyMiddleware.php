<?php

/*
   When validating the API key, with THIS middleware, if X-Api-Key is not set
   call out that there was no API key given, otherwise validate it.
   When deugging this stuff, dont forget var_dump($var) ; exit();
*/


namespace App\Application\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class ApiKeyMiddleware implements Middleware {
    /**
     * @var string
     * @var array
     * @var array
     */
    private $sentApiKey;
    private $api_auth_keys;
    private $apiKey;
    /**
     * ApiKeyMiddleware constructor.
     * @param string $apiKey
     */
    public function __construct(ContainerInterface $c) {  // Builds from dependency.php and value pulled from settings.php
        $this->apiKey = (array)$c->get('api_auth_keys');
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface {
        /*
         *   API keys must use the X-Api-Key header when calling exclusive APIs
         *
         *   At no point should an end user have X-Api-Key set
         *   At no point will one of the existing API's have a JWT
         * */
        $header = $request->getHeaders();
        if (isset($header["X-Api-Key"])) {
          $sentApiKey = $header['X-Api-Key'];
          $sentApiKey = $sentApiKey[0];
        }
        else {
          $sentApiKey = '';                                                                                                               // return empty string if header is not set
          $tempResult = "{\"statusCode\":401,\"error\": { \"type\": \"UNAUTHENTICATED\",\"description\": \"No API key given\" }}";        // Return that no key was given
        }

        if (in_array($sentApiKey, $this->apiKey)) {                                                                                       // compare string from X-Api-Key to our array for auth
          return $handler->handle($request);
        }
        else {
          if ( ! isset($tempResult)) {
            $tempResult = "{\"statusCode\":401,\"error\": { \"type\": \"UNAUTHENTICATED\",\"description\": \"Invalid API key given\" }}"; // Match what a login failure gives
          }
        }
        $response = new Response();
        $response->getBody()->write($tempResult);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);                                                // Nice example of how to send json with a specific HTML response.  Even if it is get stuffed
    }
}
