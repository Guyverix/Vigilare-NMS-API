<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonoLogger;
use Monolog\Processor\UidProcessor;

class ExternalLogger
{
    const SEVERITY_DEBUG    = 1;
    const SEVERITY_INFO     = 2;
    const SEVERITY_WARNING  = 3;
    const SEVERITY_ERROR    = 4;
    const SEVERITY_CRITICAL = 5;
    const SEVERITY_FATAL    = 5;

    public string $loggerFile = '';
    public ?string $initError = null;

    private string $appName = '';
    private int $severityThreshold;
    private string $iterationSuffix = '';
    private bool $jsonOutput = false;

    private ?MonoLogger $logger = null;
    private ?StreamHandler $handler = null;

    public function __construct(
        string $app = "unknownApplication",
        int $severityThreshold = 1,
        int $iterationCycle = 0,
        bool $jsonOutput = true
    ) {
        $this->severityThreshold = $severityThreshold;
        $this->iterationSuffix = $iterationCycle > 0 ? "_{$iterationCycle}" : "";

        $baseName = basename($app);
        $baseName = preg_replace('/\.php$/', '', $baseName);
        $baseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $baseName);

        $this->appName = $baseName . $this->iterationSuffix;
        $this->jsonOutput = $jsonOutput;

        $logDir = __DIR__ . '/../logs/';
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0755, true) && !is_dir($logDir)) {
                $this->initError = "Unable to create log directory: {$logDir}";
                return;
            }
        }

        $this->loggerFile = $logDir . $this->appName . '.log';

        try {
            $this->logger = new MonoLogger($this->appName);
            $this->logger->pushProcessor(new UidProcessor());

            $this->logger->pushProcessor(function ($record) {
              $ctx = $record['context'] ?? [];

              $record['extra']['search_text'] = implode(' | ', array_filter([
                $record['message'],
                $ctx['output'] ?? '',
                $ctx['command'] ?? '',
                $ctx['data']['hostname'] ?? '',
                $ctx['data']['isAlive'] ?? ''
              ]));

            return $record;
            });

            $this->handler = new StreamHandler($this->loggerFile, MonoLogger::DEBUG);

            if ($this->jsonOutput) {
                $this->handler->setFormatter(new JsonFormatter());
            }

            $this->logger->pushHandler($this->handler);
        } catch (\Throwable $e) {
            $this->initError = "Logger initialization failed: " . $e->getMessage();
        }
    }

    public function setJsonOutput(bool $enabled): void
    {
        $this->jsonOutput = $enabled;

        if ($this->handler !== null) {
            if ($enabled) {
                $this->handler->setFormatter(new JsonFormatter());
            } else {
                $this->handler->setFormatter(null);
            }
        }
    }

    private function log(int $level, string $label, string $message): void
    {
        if ($level < $this->severityThreshold) {
            return;
        }

        if ($this->initError !== null) {
            error_log($this->initError);
            return;
        }

        if ($this->logger === null) {
            error_log("Logger not initialized for {$this->appName}");
            return;
        }

        $context = [
            'severity' => $label,
            'severity_id' => $level,
            'application' => $this->appName,
        ];

        switch ($level) {
            case self::SEVERITY_DEBUG:
                $this->logger->debug($message, $context);
                break;

            case self::SEVERITY_INFO:
                $this->logger->info($message, $context);
                break;

            case self::SEVERITY_WARNING:
                $this->logger->warning($message, $context);
                break;

            case self::SEVERITY_ERROR:
                $this->logger->error($message, $context);
                break;

            case self::SEVERITY_CRITICAL:
            case self::SEVERITY_FATAL:
                $this->logger->critical($message, $context);
                break;

            default:
                $this->logger->info($message, $context);
                break;
        }
    }

    public function debug(string $message): void
    {
        $this->log(self::SEVERITY_DEBUG, 'DEBUG', $message);
    }

    public function info(string $message): void
    {
        $this->log(self::SEVERITY_INFO, 'INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->log(self::SEVERITY_WARNING, 'WARNING', $message);
    }

    public function warn(string $message): void
    {
        $this->warning($message);
    }

    public function error(string $message): void
    {
        $this->log(self::SEVERITY_ERROR, 'ERROR', $message);
    }

    public function critical(string $message): void
    {
        $this->log(self::SEVERITY_CRITICAL, 'CRITICAL', $message);
    }

    public function fatal(string $message): void
    {
        $this->log(self::SEVERITY_FATAL, 'FATAL', $message);
    }
}
