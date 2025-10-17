<?php

namespace App\Log;

use Monolog\Formatter\JsonFormatter;
use Monolog\Processor\IntrospectionProcessor;
use Illuminate\Log\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;

class LogFormatter
{

    public function __invoke(Logger $logger)
    {
        $formatter = new JsonFormatter(includeStacktraces: true);
        $formatter->setDateFormat("U");
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
            $handler->pushProcessor(function($record) {
                if (array_key_exists('class', $record->extra)) {
                    $record->extra['class'] = last(explode('\\', $record->extra['class']));
                }
                return $record;
            });
            $handler->pushProcessor(new IntrospectionProcessor(skipClassesPartials: ['Illuminate']));
            $handler->pushProcessor(new PsrLogMessageProcessor('U'));
            $handler->pushProcessor(new UidProcessor(8));

        }
    }
}