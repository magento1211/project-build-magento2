<?php
namespace Adyen\Payment\Logger;

use Monolog\Logger;

class AdyenLogger extends Logger
{
    /**
     * Adds a log record.
     *
     * @param  integer $level   The logging level
     * @param  string  $message The log message
     * @param  array   $context The log context
     * @return Boolean Whether the record has been processed
     */
    public function addRecord($level, $message, array $context = [])
    {
        $context['is_exception'] = $message instanceof \Exception;
        return parent::addRecord($level, $message, $context);
    }
}