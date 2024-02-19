<?php
declare(strict_types=1);

namespace App\Application\Actions\Auth;

use App\Domain\User\User;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Codes50\Validator;
use App\Application\Settings\SettingsInterface;
use Slim\Exception\HttpUnauthorizedException;

class CreateTokenAuthAction extends AuthAction {
    /**
     * {@inheritdoc}
     * @throws HttpUnauthorizedException
     */
    public $jwt_expire;

    protected function action(): Response {
       // $this->jwt_expire set in __construct to pull from settings.php
       $params = $this->getFormData();
       $this->logger->debug("Login attempt with credentials " . json_encode($params,1));
       $credentials = [
            "username" => $params['username'],
            "password" => $params['password']
        ];
        $validation = Validator::make($credentials, [
            "username" => [
                Validator::ATTR_TYPE => Validator::TYPE_STRING,
                Validator::ATTR_REQUIRED => true,
                Validator::ATTR_MAX_LENGTH => 255,
                Validator::ATTR_MIN_LENGTH => 3
            ],
            "password" => [
                Validator::ATTR_TYPE => Validator::TYPE_STRING,
                Validator::ATTR_REQUIRED => true
            ]
        ]);
        if ($validation->validate()) {
            $user = $this->userRepository->findUserOfUsernamePassword($credentials["username"], $credentials["password"], $this->pepper );
              if ( ! is_null($user)) {
                $user = json_decode(json_encode($user[0],1), true);
                if ( isset($user['timer'])) {
                  $this->jwt_expire = "+" . $user['timer'] . " hours";
                }

                $token = $this->createToken($user, $this->jwt_expire);

                $this->logger->info("Login User of id " . $user['id'] . " auth token created for " . $user['realName']);
                $this->logger->debug("Login success debug GUI data " . json_encode($_SERVER,1));
                $data = [
                    "token" => $token,
                    "user" => $user
                ];
                /*
                  Using respondWithDataHeaders we can add a cookie to the login success value
                  Otherwise the UI is going to have to save the JWT elsewhere.  Cookie seems
                  the most reasonable, but different UIs will want to do different things

                  Setting the header to null will only return session and the HTML JSON response
                  for the UI to consume

                  The JWT expires at the same time in the cookie as it does in the normal return result
                  The lifetime is defined here CURRENTLY.

                  Expiration timer is set in app/settings.php, along with JWT secret, etc..

                */

                //$header=null;  // Decide if we are using cookies or not
                $header['token'] = $data['token'];
                $header['expire'] = $this->jwt_expire;
                $data['user']['accessList'] = 'redacted';
                $data['user']['expire'] = $this->jwt_expire;
                if (isset($_SERVER)) {
                  $data['user']['apiServer'] = $_SERVER['SERVER_NAME'];  // return whatever they called to get to us so cookies are sane
                  $data['user']['apiIp'] = $_SERVER['SERVER_ADDR'];
                }
                return $this->respondWithDataHeaders($data, 201, $header);
             }
             else {
                $this->logger->error("Login failure" . json_encode($user,1));
                throw new HttpUnauthorizedException($this->request, "Username or password incorrect, or account is not active.");
             }
        }
        else {
            $this->logger->error("Login failure validation "  . json_encode($user,1));
            throw new HttpUnauthorizedException($this->request, "Validation Failed: " . json_encode($validation->error->all()));
        }
    }
    /**
    * @param User $user
    * @param string $exp_date
    * @return string
    */
    private function createToken(array $user, string $exp_date): string {
    //    private function createToken(array $user, string $exp_date = "+1 hours"): string {
    //    private function createToken(array $user, string $exp_date = "$expiration"): string {
      if ( ! empty($_SERVER)) {
        $jwtIssue = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];  // Lets have the server creating the token actually report it dynamically, okay?
      }
      else {
         $jwtIssue = 'http://127.0.0.1:80';                                      // Need a default if SERVER does not return usable data
      }
      $timezone = "";
      if (date_default_timezone_get()) {
        $timezone = date_default_timezone_get();
      }
      elseif (ini_get('date.timezone')) {
        $timezone = ini_get('date.timezone');
      }
      $token = [
        // "iss" => "http://255.255.255.255:8080", // We should be giving a FQDN, not IP address
        "iss" => $jwtIssue,
        "iat" => time(),
        "nbf" => time(),
        "exp" => strtotime($exp_date),
        "timezone" => $timezone,
        "data" => [
           'id' => $user['id'],
           'username' => $user['userId'],
           'realName' => $user['realName'],
           'accessList' => $user['accessList'],
           'jwtLife' => $exp_date,
        ],
      ];
      $jwt = JWT::encode($token, $this->secret_key);
      return $jwt;
    }
}

