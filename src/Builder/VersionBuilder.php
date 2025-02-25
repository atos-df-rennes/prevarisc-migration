<?php

namespace App\Builder;

use App\VersionType\EnvironmentType;
use App\VersionType\GithubType;
use App\VersionType\VersionType;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class VersionBuilder
{
    /** @var string */
    private $srcDir;

    /** @var HttpClientInterface */
    private $client;

    /** @var SerializerInterface */
    private $serializer;

    public const REPO_URL = 'https://github.com/atos-df-rennes/prevarisc';

    public function __construct(string $srcDir, HttpClientInterface $client, SerializerInterface $serializer)
    {
        $this->srcDir = $srcDir;
        $this->client = $client;
        $this->serializer = $serializer;
    }

    public function make(): VersionType
    {
        if (false !== getenv('PREVARISC_BRANCH')) {
            return new EnvironmentType();
        }

        return new GithubType($this->srcDir, $this->client, $this->serializer);
    }
}
