<?php

namespace App\Dto\GithubApi;

final class GithubObject
{
    public function __construct(private readonly string $sha)
    {
    }

    public function sha(): string
    {
        return $this->sha;
    }
}
