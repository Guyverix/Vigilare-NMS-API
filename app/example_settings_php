<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;


return function (ContainerBuilder $containerBuilder) {
    // Global Settings Object
    $containerBuilder->addDefinitions([
        'settings' => [
            'displayErrorDetails' => true, // Should be set to false in production
            'logger' => [
                'name' => 'nms-api',       // Generic prefix inside the log ilfe.  This should actually reflect class paths in the future
                'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/apiCalls.log',
                'level' => Logger::DEBUG,   // Default log level
            ],
        ],
        'superSecretSettingsValue' => 'This is a super secret settings value from settings.php',  // This is for testing retrieving sensitive values
        'secret_key' => '???',                                      // randomkeygen.com (to make our JWT)
        'jwt_encryption' => ['HS256'],                              // currently only version tested. (dont change unless testing)
        'jwt_secure' => 'false',                                    // only ssl connections? what happens with expired certs?  do we die?
        'jwt_expire' => '+2 hours',                                 // how long does the token live? defined on a per acct basis, this is fallback
        'api_auth_keys' => ["1234fake5", "9876stillFake54321"],     // Each Polling server should have its own key defined and set here for auth
        'passwordPepper' => '???randomString???',                   // Pepper value to encrypt db passwords for users + sensitive stuff
        'frontendUrl' => 'https://FQDN',
    ]);
};

