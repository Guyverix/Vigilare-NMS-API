<?php
declare(strict_types=1);

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Application\ResponseEmitter\ResponseEmitter;
//use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;

require __DIR__ . '/../vendor/autoload.php';


/* Testing beginning of auth */
// CSH session_start();
// Session lifetime in seconds

/*
CSH
$inactividad = 300;
if (isset($_SESSION["timeout"])){
    $sessionTTL = time() - $_SESSION["timeout"];
    if ($sessionTTL > $inactividad) {
        session_destroy();
        header("Location: /");
    }
}
*/
// Start timeout for later check
// CSH $_SESSION["timeout"] = time();
/* Testing end of changes for auth */







// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

if (false) { // Should be set to true in production
	$containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

// Serve up images from the API server for the UI to consume
// https://stackoverflow.com/questions/32191920/serve-images-assests-using-slim-framework
$s=$_SERVER['REQUEST_URI'];
$e=substr($s,-4 );
if ((substr($s,0,8)=='/static/') &&
   (($e=='.gif') || ($e=='.png') || ($e=='.jpg'))
   ) { if (!file_exists(htmlentities('../'.$s))) { exit('No such file.');
       } else {header("Content-type: image/".($e=='.jpg'?'jpeg':substr($e,1)));
       readfile(htmlentities('../'.$s)); exit();
       }
}

// Set up settings
$settings = require __DIR__ . '/../app/settings.php';
$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Set up repositories
$repositories = require __DIR__ . '/../app/repositories.php';
$repositories($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();
$callableResolver = $app->getCallableResolver();

// Register middleware
$middleware = require __DIR__ . '/../app/middleware.php';
$middleware($app);

// Register routes
$routes = require __DIR__ . '/../app/routes.php';
$routes($app);

/** @var bool $displayErrorDetails */
//$displayErrorDetails = $container->get(SettingsInterface::class)->get('displayErrorDetails');
$displayErrorDetails = $container->get('settings')['displayErrorDetails'];


// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// Create Error Handler
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
register_shutdown_function($shutdownHandler);

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, false, false);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// Run App & Emit Response
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
