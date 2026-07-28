<?php

namespace App\Dto;

use App\Dto\GithubApi\GithubObject;

final class GithubApi
{
    public function __construct(private readonly string $ref, private readonly GithubObject $object)
    {
    }

    public function ref(): string
    {
        return $this->ref;
    }

    public function object(): GithubObject
    {
        return $this->object;
    }
}
