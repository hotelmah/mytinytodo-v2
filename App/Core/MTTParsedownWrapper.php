<?php

declare(strict_types=1);

/*
    This file is a part of myTinyTodo.
    (C) Copyright 2022-2023 Max Pozdeev <maxpozdeev@gmail.com>
    Licensed under the GNU GPL version 2 or any later. See file COPYRIGHT for details.
*/

namespace App\Core;

class MTTParsedownWrapper implements MTTMarkdownInterface
{
    /** @var MTTParsedown */
    protected $converter;

    public function __construct()
    {
        $this->converter = new MTTParsedown();
        $this->converter->setSafeMode(true);
        //$this->converter->setBreaksEnabled(true);
    }

    public function convert(string $s, bool $toExternal = false)
    {
        $this->converter->setToExternal($toExternal);
        return $this->converter->text($s);
    }
}
