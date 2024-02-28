<?php

namespace Custom_Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

class Custom_Logger
{
    public $logResult;
    public $logError;

    public function __construct()
    {
        $dateFormat = "Y:m:d, H:i:s e";
        $output = "%datetime% > %message%\n";
        $formatter = new LineFormatter($output, $dateFormat);

        $streamResult = new StreamHandler("operationResult.log", Level::Info);
        $streamResult->setFormatter($formatter);
        $this->logResult = new Logger('operationResult');
        $this->logResult->pushHandler($streamResult);

        $formatterError = new LineFormatter(null, null, false, false, true);
        $streamError = new StreamHandler("errors.log", Level::Error);
        $streamError->setFormatter($formatterError);
        $this->logError = new Logger('error');
        $this->logError->pushHandler($streamError);
    }

    public function logSuccess($message)
    {
        $this->logResult->info($message);
    }

    public function logError($error)
    {
        $this->logError->error($error);
    }
}