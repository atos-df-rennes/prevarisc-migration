<?php

namespace App\VersionType;

use App\Builder\VersionBuilder;
use App\Dto\GithubApi;
use Composer\Semver\Comparator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class EnvironmentType implements VersionType
{
    public function __construct(private readonly HttpClientInterface $client, private readonly SerializerInterface $serializer, private readonly LoggerInterface $logger)
    {
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
        try {
            $githubApiResponse = $this->client->request('GET', VersionBuilder::REPO_API_URL.'/git/refs/tags')->getContent();
        } catch (\Exception $exception) {
            $this->logger->notice("La vérification des tags sur le dépôt n'a pas pu être effectuée : ".$exception->getMessage());

            return true;
        }

        $githubApiResponseToDto = $this->serializer->deserialize($githubApiResponse, GithubApi::class.'[]', 'json');

        $tagRefs = array_map(fn ($tag) => $tag->ref(), $githubApiResponseToDto);
        $latestTagRef = end($tagRefs);

        if (false === $latestTagRef) {
            throw new \Exception('Aucune référence trouvée.');
        }

        $latestTag = explode('/', (string) $latestTagRef);
        $latestTag = end($latestTag);

        return Comparator::equalTo($latestTag, $this->version());
    }
}
