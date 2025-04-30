<?php

declare(strict_types=1);

namespace App\Core;

interface MTTSmartSyntaxInterface
{
    public function parse(string $title): array;
}
