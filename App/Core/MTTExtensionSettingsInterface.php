<?php

declare(strict_types=1);

namespace App\Core;

interface MTTExtensionSettingsInterface
{
    public function settingsPage(): string;
    public function settingsPageType(): int;
    public function saveSettings(array $array, ?string &$outMesssage): bool;
}
