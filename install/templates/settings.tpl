<?php
declare(strict_types=1);
use DI\ContainerBuilder;
use Monolog\Logger;
return function (ContainerBuilder \$containerBuilder) {
    // Global Settings Object
    \$containerBuilder->addDefinitions([
        \'settings\' => [
            \'displayErrorDetails\' => true,                                                            // Should be set to false in production
            \'logger\' => [
                \'name\' => \'nms-api\',
                \'path\' => isset(\$_ENV[\'docker\']) ? \'php://stdout\' : __DIR__ . \'/../logs/apiCall.log\', // Generic catchall log file for all paths
                \'level\' => Logger::DEBUG,
            ],
        ],
        \'superSecretSettingsValue\' => \'This is a super secret settings value from settings.php\',      // testing retreval of values
        \'secret_key\' => \"${JWT_SECRET}\",                                                              // randomkeygen.com if you want better than created here
        \'jwt_encryption\' => [\'HS256\'],                                                                // Add this in later.  Hard coded for now
        \'jwt_secure\' => \'false\',                                                                      // only ssl connections? (expired SSL will cause failures)
        \'jwt_expire\' => \'+2 hours\',                                                                   // how long does the token live if not defined for user
        \'api_auth_keys\' => [\"${API_KEY}\"],                                                            // Each Polling server must add their unique key to this array
        \'passwordPepper\' => \"${PEPPER}\",                                                              // Pepper value to encrypt db passwords for users + sensitive stuff
        \'frontendUrl\' => \"https://${GUI_FQDN}\",                                                       // So the backend can create frontend URL\'s when needed
    ]);
};

