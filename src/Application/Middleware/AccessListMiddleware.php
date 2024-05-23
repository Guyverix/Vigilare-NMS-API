<?php

/*
   https://github.com/CodeS50/slim-framework-4-jwt-auth-example/issues/2
   Great info on dealing with this kind of issue.  Example given in issue
   appears to address common problems with access.

   When deugging this stuff, dont forget var_dump($var) ; exit();
*/


namespace App\Application\Middleware;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;             // Used for finding what our route context is

class AccessListMiddleware implements Middleware
{
    /**
     * @var string
     */
    private $table_name;
    /**
     * AccessListMiddleware constructor.
     * @param string $table_name
     */
    public function __construct(string $table_name)
    {
        // poor naming convention, this is what we match against for access levels
        $this->table_name = $table_name;  // Passed from route.php as a string version of the template name.  {foo} would be sent here as "foo"
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        /*
        * You can keep role values in JWT.
        *   In this way, you can verify as follows.
        *
        * Data assigned with withAttribute in JwtAuthenticationMiddleware (return $request->withAttribute("user", $data);)
        *
        * This should be a string that matches your access level name.  If you are admin, you will have user,tech,operator,admin in your token=>data=>accessList string.
        * This gives you access to everything under the routeArguments
        *
        * In the future we may go more granular, but do not see a reason right now
        * */

        $jwt_public_data = $request->getAttribute("user");                            // retrieve token data for user
        $jwt_public_data = json_decode(json_encode($jwt_public_data,1),true);         // convert object to array
        if ( isset ($jwt_public_data["accessList"])) {
          $createArray = explode(',', $jwt_public_data["accessList"]);                // accessList stored as csv string.  Convert to array
        }
        else {
          $createArray = array();                                                     // return empty array if user is not set at all
        }

        // BAD if (is_integer((int)$this->table_name)) {                                     // We are only working with number here.  >= all good, else fail
        if (filter_var($this->table_name, FILTER_VALIDATE_INT)) {                     // We are only working with number here.  >= all good, else fail
          foreach ($createArray as $validAccessLevels) {
            if (is_integer((int)$validAccessLevels)) {
              if ($validAccessLevels >= (int)$this->table_name) {
                return $handler->handle($request);
              }
            }
          }
        }
        else {                                                                        // We have a mix or string as CSV  (0,admin) (100,foobar)
          // First test string Match
          $jwt_public_data["accessList"] = $createArray;
          foreach ($createArray as $checkAccess) {
            $checkAccess = trim($checkAccess); // remove any spaces in string
            if (in_array($checkAccess, $jwt_public_data["accessList"])) {
              return $handler->handle($request);
            }
          }
          // Attempt then attempt integer aval
          foreach ($createArray as $checkAccess) {
            if ( filter_var($checkAccess, FILTER_VALIDATE_INT) ) {
              foreach ($createArray as $userAccess) {
                if ( filter_var($userAccess, FILTER_VALIDATE_INT) ) {
                  if ( $userAccess >= $checkAccess ) {
                    return $handler->handle($request);
                  }
                }
              }
            }
          }
        }
/*
        Anything below this point is a failure.  We either have access above, or at this point we fail


        $response = new Response();
        $response->getBody()->write('TESTING ' . json_encode($createArray,1));
        return $response->withStatus(418);

        $jwt_public_data["accessList"] = $createArray;                                // Add array back into jwt_public_data..
        if (in_array($this->table_name, $jwt_public_data["accessList"])) {            // compare string from routeArguments to our array of strings we have access to
          return $handler->handle($request);                                          // SUCCESS route, script ends here on success
        }
*/
        $response = new Response();
        $response->getBody()->write('Additional access required.  Contact admin.');   // FAILURE route, script ends here on failure
        return $response->withStatus(418);
    }
}
