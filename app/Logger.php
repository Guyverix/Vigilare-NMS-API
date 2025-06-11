<?php
//declare(strict_types=1);

// https://gist.github.com/bradtraversy/a77931605ba9b7cf3326644e75530464
// Using example for PDO class to make new generic logger class
class ExternalLogger {
  private $fatal;
  private $critical;
  private $error;
  private $warning;
  private $info;
  private $debug;
  public $loggerFile;
  public $objectError;
  public $app;
  public $appName;
  private $sev=1; // This is the minimum severity to log 1 - 5
  private $iterationCycle;

  // Build out logging defaults
  public function __construct(?string $app="unknownApplication", ?int $sev = 0 , ?int $iterationCycle= 0){
    // If we are given a minimum severity to log, update
    // so all functions know it
    $this->app=$app;
    $this->sev=$sev;
    if ( $iterationCycle > 0 ) {
      $this->iterationCycle="_$iterationCycle";
    }
    else {
      $this->iterationCycle="";
    }
    // Clean our application names to make a nice logfile name
    // Never assume we will have a .php in the filename however
    $this->appName=(preg_replace('/.php/','',$this->app));
    $this->loggerFile= __DIR__ . '/../logs/' . $this->appName . '.log';
    $this->appName .= $this->iterationCycle;
    // Touch file
    echo "loggerFile " . $this->loggerFile . "\n";
    if (! touch($this->loggerFile)) {
      $this->error="Unable to touch logfile: $this->loggerFile";
      return;
    }
  }

  public function fatal($details) {
    $logSev=5;
    if ( $logSev >= $this->sev ) {
      $logName='FATAL';
      $logDetails='['. date('Y-m-d\TH:i:s') . "] " . $this->appName . " [Severity: " . $logName . " - " . $details . "] {\"severity\":\"" . $logSev . "\"}\n";
      file_put_contents($this->loggerFile, $logDetails, FILE_APPEND);
    }
  }

  public function critical($details) {
    $logSev=5;
    if ( $logSev >= $this->sev ) {
      $logName='CRITICAL';
      $logDetails='['. date('Y-m-d\TH:i:s') . "] " . $this->appName . " [Severity: " . $logName . " - " . $details . "] {\"severity\":\"" . $logSev . "\"}\n";
      file_put_contents($this->loggerFile, $logDetails, FILE_APPEND);
    }
  }

  public function error($details) {
    $logSev=4;
    if ( $logSev >= $this->sev ) {
      $logName='ERROR';
      $logDetails='['. date('Y-m-d\TH:i:s') . "] " . $this->appName . " [Severity: " . $logName . " - " . $details . "] {\"severity\":\"" . $logSev . "\"}\n";
      file_put_contents($this->loggerFile, $logDetails, FILE_APPEND);
    }
  }

  public function warning($details) {
    $logSev=3;
    if ( $logSev >= $this->sev ) {
      $logName='WARNING';
      $logDetails='['. date('Y-m-d\TH:i:s') . "] " . $this->appName . " [Severity: " . $logName . " - " . $details . "] {\"severity\":\"" . $logSev . "\"}\n";
      file_put_contents($this->loggerFile, $logDetails, FILE_APPEND);
    }
  }

  public function warn($details) {
    $logSev=3;
    if ( $logSev >= $this->sev ) {
      $logName='WARNING';
      $logDetails='['. date('Y-m-d\TH:i:s') . "] " . $this->appName . " [Severity: " . $logName . " - " . $details . "] {\"severity\":\"" . $logSev . "\"}\n";
      file_put_contents($this->loggerFile, $logDetails, FILE_APPEND);
    }
  }

  public function info($details) {
    $logSev=2;
    if ( $logSev >= $this->sev ) {
      $logName='INFO';
      $logDetails='['. date('Y-m-d\TH:i:s') . "] " . $this->appName . " [Severity: " . $logName . " - " . $details . "] {\"severity\":\"" . $logSev . "\"}\n";
      file_put_contents($this->loggerFile, $logDetails, FILE_APPEND);
    }
  }

  public function debug($details) {
    $logSev=1;
    if ( $logSev >= $this->sev ) {
      $logName='DEBUG';
      $logDetails='['. date('Y-m-d\TH:i:s') . "] " . $this->appName . " [Severity: " . $logName . " - " . $details . "] {\"severity\":\"" . $logSev . "\"}\n";
      file_put_contents($this->loggerFile, $logDetails, FILE_APPEND);
    }
  }
}

?>
