<?php

namespace App\Dto;

use App\Dto\GithubApi\GithubObject;

final readonly class GithubApi
{
    public function __construct(private string $ref, private GithubObject $object)
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
