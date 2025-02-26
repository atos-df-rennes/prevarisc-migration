<?php

namespace App\Dto;

use App\Dto\GithubApi\GithubObject;

final class GithubApi
{
    /** @var string */
    private $ref;

    /** @var GithubObject */
    private $object;

    public function __construct(string $ref, GithubObject $object)
    {
        $this->ref = $ref;
        $this->object = $object;
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
