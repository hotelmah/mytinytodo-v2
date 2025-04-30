<?php

declare(strict_types=1);

namespace App\Core;

interface MTTHttpApiExtender
{
    public function extendHttpApi(): array;
}
