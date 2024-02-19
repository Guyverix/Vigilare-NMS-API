<?php

// Includes for logging etc go up here
//namespace Poller\Poller;
use Psr\Log\LoggerInterface;
use Slim\Logger;
//use SNMP;

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
fwrite($pidFile, getmypid());


class Foo {
//  protected $logger;
//  private $logger;
  public function __construct(LoggerInterface $logger = log) {
//  public function __construct(LoggerInterface $logger) {
//    $this->logger = null === $logger ? new Foo() : $logger;
//$this->logger = $logger ?: new Foo();
    $this->logger = $logger;
  }
//  public function doSomething() {
  public function doSomething($data) {
//    if ($this->logger) {
      $this->logger->doSomething("$data");
//    }
//  public function info($data) {
//    if ($this->logger) {
//      $this->logger->info("$data");
//    }
/*    try {
      $this->doSomethingElse();
    }
    catch (Exception $exception) {
      $this->logger->error('Oh no!', array('exception' => $exception));
    }
  // do something useful
*/
  }
}

$logger = new Foo();
print_r($logger);

//$this->logger = $logger;

//while (true) sleep(1);
while (true) {
  $session = new SNMP(SNMP::VERSION_1, "192.168.15.58", "public");
  $sysdescr = $session->get("1.3.6.1.2.1.1.6.0");
//  $this->logger->info("SNMP get " . $sysdescr . ".");
//  $this->logger->doSomething("SNMP get " . $sysdescr . ".");
  $logger->doSomething("SNMP get " . $sysdescr . ".");
//  $logger->info("SNMP get " . $sysdescr . ".");
print_r($logger->info);
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
