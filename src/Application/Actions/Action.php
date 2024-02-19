<?php
declare(strict_types=1);

namespace App\Application\Actions;

use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

abstract class Action
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var array
     */
    protected $args;

    /**
     * @param LoggerInterface $logger
     */

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        try {
            return $this->action();
        } catch (DomainRecordNotFoundException $e) {
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }catch (ValidationException $e) {
            throw new HttpBadRequestException($this->request, $e->getMessage());
        }
    }

    /**
     * @return Response
     * @throws DomainRecordNotFoundException
     * @throws HttpBadRequestException
     */
    abstract protected function action(): Response;


/* https://github.com/slimphp/Slim-Skeleton/issues/222  REPLACED getFormData() */

    protected function getFormData(): array
    {
        return $this->request->getParsedBody() ?? [];
    }
/**
     * @return array|object
     * @throws HttpBadRequestException
    protected function getFormData()
    {
        $input = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HttpBadRequestException($this->request, 'Malformed JSON input.');
        }

        return $input;
    }
*/

/**
     * @return array|object
     * @throws HttpBadRequestException
*/

    /* This is the origional function disabled in issue/222 renamed to still be able to be used */
    protected function getFormDataJson() {
      $input = json_decode(file_get_contents('php://input'));
      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new HttpBadRequestException($this->request, 'Malformed JSON input.');
      }
      return $input;
    }

    /**
     * @param  string $name
     * @return mixed
     * @throws HttpBadRequestException
     */
    protected function resolveArg(string $name)
    {
        if (!isset($this->args[$name])) {
            throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
        }

        return $this->args[$name];
    }

    /**
     * @param array|object|null $data
     * @param int $statusCode
     * @return Response
     */
    protected function respondWithData($data = null, int $statusCode = 200): Response
    {
        $payload = new ActionPayload($statusCode, $data);

        return $this->respond($payload);
    }

    /**
     * @param ActionPayload $payload
     * @return Response
     */
    protected function respond(ActionPayload $payload): Response
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($json);

        return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus($payload->getStatusCode());
    }



    protected function respondWithDataHeaders($data = null, int $statusCode = 200, $headers = null): Response {
        $payloadHeaders = new ActionPayload($statusCode, $data);
        return $this->respondHeaders($payloadHeaders, $headers);
    }

    /**
     * @param ActionPayload $payload
     * @return Response
     */
    protected function respondHeaders(ActionPayload $payload, $headers): Response {
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($json);
        if ( $headers !== null ) {
          $token  = $headers['token'];
          $expire = strtotime($headers['expire'], time());
          return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withHeader('Location', 'index.php?')
                    ->withAddedHeader('Set-Cookie', 'Authentication=' .$token. ' ; Path=/; Expires=' . gmdate('D, d M Y H:i:s T', $expire) . '; SameSite=Strict' )
//                    ->withAddedHeader('Set-Cookie', 'Authentication=' .$token. ' ; Path=/; Expires=' . gmdate('D, d M Y H:i:s T', $expire) . '; SameSite=Strict' )
//                    ->withAddedHeader('Set-Cookie', 'SameSite=Strict ; Path=/;')
//                    ->withAddedHeader('Set-Cookie', 'domain=iwillfearnoevil.com ; Path=/;')
                    ->withStatus($payload->getStatusCode());
        }
        else {
          return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus($payload->getStatusCode());
        }
    }
}
