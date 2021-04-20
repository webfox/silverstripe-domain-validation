<?php
namespace Codem\DomainValidation;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;

/**
 * Ultra basic logging handler for SS_Log compat
 */
class Log
{
    public static function log($message, $level = 'DEBUG', array $context = [])
    {
        Injector::inst()->get(LoggerInterface::class)->log($level, (string) $message, $context);
    }
}
