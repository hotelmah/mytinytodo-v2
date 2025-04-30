<?php

declare(strict_types=1);

namespace App\Core;

interface MTTNotificationObserverInterface
{
    public function notification(string $notification, $object);
}
