<?php

namespace App\VersionType;

interface VersionType
{
    public function version(): string;
    public function link(): string;
    public function isUpToDate(): bool;
}
