<?php

namespace App\VersionType;

use App\Builder\VersionBuilder;
use App\Dto\GithubApi;
use SebastianBergmann\Git\Git;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

    public function __construct(string $srcDir, HttpClientInterface $client, SerializerInterface $serializer)
    {
        $this->client = $client;
        $this->serializer = $serializer;

        $git = new Git(dirname($srcDir).'/prevarisc');

        $this->branch = $git->getCurrentBranch();

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
        $githubApiResponse = $this->client->request('GET', 'https://api.github.com/repos/atos-df-rennes/prevarisc/git/refs/heads/'.$this->branch)->getContent();
        $githubApiResponseToDto = $this->serializer->deserialize($githubApiResponse, GithubApi::class, 'json');

        return $this->revision === $githubApiResponseToDto->object()->sha();
    }
}
