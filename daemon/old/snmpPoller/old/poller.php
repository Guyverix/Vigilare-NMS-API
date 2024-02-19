<?php
//declare(strict_types=1);

/*
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
*/
use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;
use Slim\Factory\AppFactory;

//use Psr\Log\LoggerInterface;

// Includes for logging etc go up here
//use Psr\Log\LoggerInterface;
//use Slim\Logger;
//use SNMP;

require __DIR__ . '/../vendor/autoload.php';
$settings = require __DIR__ . '/../app/settings.php';

// Instantiate PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();
if (false) { // Should be set to true in production
        $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
}

$settings($containerBuilder);

// Set up dependencies
$dependencies = require __DIR__ . '/../app/dependencies.php';
$dependencies($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();


// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();
$callableResolver = $app->getCallableResolver();


// Support daemon shutdown

pcntl_async_signals(true);
pcntl_signal(SIGTERM, 'signalHandler');// Termination ('kill' was called)
pcntl_signal(SIGHUP, 'signalHandler'); // Terminal log-out
pcntl_signal(SIGINT, 'signalHandler'); // Interrupted (Ctrl-C is pressed)

// pidfile so we can kill easier
$pidFileName = basename(__FILE__) . '.pid';
$pidFile = @fopen($pidFileName, 'c');
if (!$pidFile) die("Could not open $pidFileName\n");
if (!@flock($pidFile, LOCK_EX | LOCK_NB)) die("Already running?\n");
ftruncate($pidFile, 0);
// fwrite wants a string,not an int
$pid=getmypid();
//fwrite($pidFile, getmypid());
fwrite($pidFile, "$pid");

// Logger defined in Slim\Logger (works to stdout only)
//$logger = new Logger();


//$logger = new $containerBuilder->SettingsInterface->LoggerInterface;
//$logger = new $containerBuilder->LoggerInterface;
//$logger= null;
$poller="poller";
//$logger = new LoggerInterface;
//$logger = new Logger($poller);
//echo "CALLABLERESOLVER\n" . print_r($callableResolver);
//echo "CONTAINER\n" . print_r($container);
//echo "CONTAIERBUILDER\n" . print_r($containerBuilder);
//echo "SETTINGS\n"; var_dump($settings);
//echo "CONTAINER\n"; var_dump($container);
echo "";
//echo "VAR SETTINGS\n" . var_dump($settings);
//echo "VAR SETTINGS\n" . var_dump($settings);
//var_dump($settings);
echo "";
//$class_vars = get_class_vars(get_class($settings));
//$class_vars = get_class_vars(get_class($container));
//echo "OBJECT_VARS\n" . var_dump(get_object_vars($callableResolver));
//get_class_methods('ContainerBuilder');

//$class_vars = get_class_vars(get_class($callableResolver));
//echo "CLASS VARS\n". var_dump($class_vars);
echo "APP\n"; var_dump($app);

$app->logger->info("JUST WORK DAMMIT");

//echo "OBJ SETTINGS\n". get_object_vars($settings);
//echo "OBJ SETTINGS\n". var_dump(get_object_vars($settings));
//echo "";
//echo "OBJ CLASS VAR SETTINGS\n". get_class_vars("SettingsInterface");

//echo "LOGGER\n" . print_r($logger);
//echo "VAR LOGGER\n" . var_dump($logger);



$arr=["1.3.6.1.2.1.1.6.0", "public" , "192.168.15.58"];
//  $logger->info("info", $arr, $arr);


exit();

while (true) {
  $session = new SNMP(SNMP::VERSION_1, "192.168.15.58", "public");
  $sysdescr = $session->get("1.3.6.1.2.1.1.6.0");
//  $logger->log("info", "log SNMP get " . $sysdescr . ".", $arr);
//  $logger->info("info", "log SNMP get " . $sysdescr . ".", $arr);
//  $logger("info", "log SNMP get " . $sysdescr . ".", $arr);
  $logger->info("info", $arr, $arr);

  echo "$sysdescr\n";


sleep(3);
}

function signalHandler($signal) {
  global $pidFile;
  ftruncate($pidFile, 0);
  echo "Requested to exit";
  exit;
}




?>
