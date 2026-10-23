<?php

namespace common\helpers;

use Sentry\Event;

class SentryHelper
{
    public static function groupDbConnectionErrors(Event $event): void
    {
        $texts = [$event->getMessage() ?? ''];
        foreach ($event->getExceptions() as $exception) {
            $texts[] = $exception->getValue();
        }

        foreach ($texts as $text) {
            // PDO uses the "[code]" form only for errors raised while connecting
            if (preg_match('/SQLSTATE\[\w+\] \[\d+\]/', $text, $matches)) {
                $event->setFingerprint(['db-connection-error', $matches[0]]);
                return;
            }
        }
    }
}
