<?php

namespace App\Builder;

use App\VersionType\EnvironmentType;
use App\VersionType\GithubType;
use App\VersionType\VersionType;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class VersionBuilder
{
    public const string REPO_URL = 'https://github.com/atos-df-rennes/prevarisc-migration';

    public const string REPO_API_URL = 'https://api.github.com/repos/atos-df-rennes/prevarisc-migration';

    public function __construct(
        private string $srcDir,
        private HttpClientInterface $client,
        private SerializerInterface $serializer,
        private LoggerInterface $logger
    ) {
    }

    public function build(): VersionType
    {
        if (false !== getenv('PREVARISC_BRANCH')) {
            return new EnvironmentType($this->client, $this->serializer, $this->logger);
        }

        return new GithubType($this->srcDir, $this->client, $this->serializer, $this->logger);
    }
}
