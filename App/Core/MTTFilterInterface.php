<?php

declare(strict_types=1);

namespace App\Core;

interface MTTFilterInterface
{
    public function filter($in, &$out);
}
