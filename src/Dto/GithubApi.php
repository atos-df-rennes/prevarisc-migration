<?php

namespace App\Dto;

use App\Dto\GithubApi\GithubObject;

final class GithubApi
{
    /** @var GithubObject */
    private $object;

    public function __construct(GithubObject $object)
    {
        $this->object = $object;
    }

    public function object(): GithubObject
    {
        return $this->object;
    }
}
