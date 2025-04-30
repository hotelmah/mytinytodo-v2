<?php

declare(strict_types=1);

namespace App\Core;

interface MTTMarkdownInterface
{
    public function convert(string $s, bool $toExternal = false);
}
