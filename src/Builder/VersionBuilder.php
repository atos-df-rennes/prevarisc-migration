<?php

namespace App\Builder;

use App\VersionType\EnvironmentType;
use App\VersionType\GithubType;
use App\VersionType\VersionType;
use Psr\Log\LoggerInterface;
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

    /** @var LoggerInterface */
    private $logger;

    public const REPO_URL = 'https://github.com/atos-df-rennes/prevarisc';

    public const REPO_API_URL = 'https://api.github.com/repos/atos-df-rennes/prevarisc';

    public function __construct(string $srcDir, HttpClientInterface $client, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->srcDir = $srcDir;
        $this->client = $client;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function build(): VersionType
    {
        if (false !== getenv('PREVARISC_BRANCH')) {
            return new EnvironmentType($this->client, $this->serializer, $this->logger);
        }

        return new GithubType($this->srcDir, $this->client, $this->serializer, $this->logger);
    }
}
