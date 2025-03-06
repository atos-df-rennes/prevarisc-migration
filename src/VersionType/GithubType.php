<?php

namespace App\VersionType;

use App\Builder\VersionBuilder;
use App\Dto\GithubApi;
use Psr\Log\LoggerInterface;
use SebastianBergmann\Git\Git;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-type GitRevision array{
 *   sha1: string,
 * }
 */
final class GithubType implements VersionType
{
    /** @var string */
    private $branch;

    /** @var string */
    private $revision;

    /** @var HttpClientInterface */
    private $client;

    /** @var SerializerInterface */
    private $serializer;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $srcDir, HttpClientInterface $client, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->serializer = $serializer;
        $this->logger = $logger;

        $git = new Git(dirname($srcDir).'/prevarisc');

        $this->branch = $git->getCurrentBranch();

        /** @var GitRevision[] */
        $revisions = $git->getRevisions();
        $lastRevision = end($revisions);

        if (false === $lastRevision) {
            throw new \Exception('Aucune révision trouvée.');
        }

        $this->revision = $lastRevision['sha1'];
    }

    public function version(): string
    {
        return $this->branch.'@'.substr($this->revision, 0, 7);
    }

    public function link(): string
    {
        return VersionBuilder::REPO_URL.'/commit/'.$this->revision;
    }

    public function isUpToDate(): bool
    {
        try {
            $githubApiResponse = $this->client->request('GET', VersionBuilder::REPO_API_URL.'/git/refs/heads/2.5')->getContent();
        } catch (\Exception $exception) {
            $this->logger->notice("La vérification des commits sur le dépôt n'a pas pu être effectuée : ".$exception->getMessage());

            return true;
        }

        $githubApiResponseToDto = $this->serializer->deserialize($githubApiResponse, GithubApi::class, 'json');

        return $this->revision === $githubApiResponseToDto->object()->sha();
    }
}
