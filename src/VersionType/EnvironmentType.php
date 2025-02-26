<?php

namespace App\VersionType;

use App\Builder\VersionBuilder;
use App\Dto\GithubApi;
use Composer\Semver\Comparator;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class EnvironmentType implements VersionType
{
    /** @var HttpClientInterface */
    private $client;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(HttpClientInterface $client, SerializerInterface $serializer)
    {
        $this->client = $client;
        $this->serializer = $serializer;
    }

    public function version(): string
    {
        $branch = getenv('PREVARISC_BRANCH');
        $revision = getenv('PREVARISC_REVISION');

        return 'v'.$branch.'.'.$revision;
    }

    public function link(): string
    {
        return VersionBuilder::REPO_URL.'/releases/tag/'.$this->version();
    }

    public function isUpToDate(): bool
    {
        $githubApiResponse = $this->client->request('GET', VersionBuilder::REPO_API_URL.'/git/refs/tags')->getContent();
        $githubApiResponseToDto = $this->serializer->deserialize($githubApiResponse, GithubApi::class.'[]', 'json');

        $tagRefs = array_map(function ($tag) {
            return $tag->ref();
        }, $githubApiResponseToDto);
        $latestTagRef = end($tagRefs);

        if (false === $latestTagRef) {
            throw new \Exception('Aucune référence trouvée.');
        }

        $latestTag = explode('/', $latestTagRef);
        $latestTag = end($latestTag);

        return Comparator::equalTo($latestTag, $this->version());
    }
}
