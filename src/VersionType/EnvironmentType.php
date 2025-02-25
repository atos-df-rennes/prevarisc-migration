<?php

namespace App\VersionType;

use App\Builder\VersionBuilder;

final class EnvironmentType implements VersionType
{
    public function version(): string
    {
        $branch = getenv('PREVARISC_BRANCH');
        $revision = getenv('PREVARISC_REVISION');

        return $branch.'.'.$revision;
    }

    public function link(): string
    {
        return VersionBuilder::REPO_URL.'/releases/tag/v'.$this->version();
    }

    public function isUpToDate(): bool
    {
        return true;
    }
}
