<?php

class ExternalLogger {
  const SEVERITY_DEBUG    = 1;
  const SEVERITY_INFO     = 2;
  const SEVERITY_WARNING  = 3;
  const SEVERITY_ERROR    = 4;
  const SEVERITY_CRITICAL = 5;
  const SEVERITY_FATAL    = 5;

  public $loggerFile;
  public $initError;
  private $appName;
  private $severityThreshold;
  private $iterationSuffix;
  private $jsonOutput = false;

  public function __construct(
    ?string $app = "unknownApplication",
    int $severityThreshold = 1,
    int $iterationCycle = 0,
    bool $jsonOutput = false
  ) {
    $this->severityThreshold = $severityThreshold;
    $this->iterationSuffix = $iterationCycle > 0 ? "_$iterationCycle" : "";
    $baseName = preg_replace('/\.php$/', '', $app);
    $this->appName = $baseName . $this->iterationSuffix;
    $this->jsonOutput = $jsonOutput;

    $logDir = __DIR__ . '/../logs/';
    if (!is_dir($logDir)) {
      mkdir($logDir, 0755, true);
    }

    $this->loggerFile = $logDir . $baseName . '.log';
    if (!touch($this->loggerFile)) {
      $this->initError = "Unable to touch logfile: {$this->loggerFile}";
    }
  }

  public function setJsonOutput(bool $enabled): void {
    $this->jsonOutput = $enabled;
  }

  private function log(int $level, string $label, string $message): void {
    if ($level < $this->severityThreshold) {
      return;
    }

    $timestamp = date('Y-m-d\TH:i:s');
    $logEntry = '';

    if ($this->jsonOutput) {
      $logEntry = json_encode([
        'timestamp'   => $timestamp,
        'application' => $this->appName,
        'severity'    => $label,
        'severity_id' => $level,
        'message'     => $message
      ], JSON_UNESCAPED_SLASHES) . "\n";
    } else {
      $logEntry = "[{$timestamp}] {$this->appName} [Severity: {$label} - {$message}] {\"severity\":\"{$level}\"}\n";
    }

    file_put_contents($this->loggerFile, $logEntry, FILE_APPEND);
  }

  // Severity-specific public methods
  public function debug(string $message): void    { $this->log(self::SEVERITY_DEBUG,    'DEBUG',    $message); }
  public function info(string $message): void     { $this->log(self::SEVERITY_INFO,     'INFO',     $message); }
  public function warning(string $message): void  { $this->log(self::SEVERITY_WARNING,  'WARNING',  $message); }
  public function warn(string $message): void     { $this->warning($message); }
  public function error(string $message): void    { $this->log(self::SEVERITY_ERROR,    'ERROR',    $message); }
  public function critical(string $message): void { $this->log(self::SEVERITY_CRITICAL, 'CRITICAL', $message); }
  public function fatal(string $message): void    { $this->log(self::SEVERITY_FATAL,    'FATAL',    $message); }
}
