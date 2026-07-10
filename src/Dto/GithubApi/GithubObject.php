<?php

namespace App\Dto\GithubApi;

final class GithubObject
{
    private string $sha;

    public function __construct(string $sha)
    {
        $this->sha = $sha;
    }

    public function sha(): string
    {
        return $this->sha;
    }
}
