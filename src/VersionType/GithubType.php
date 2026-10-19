<?php

namespace App\VersionType;

use App\Builder\VersionBuilder;
use App\Dto\GithubApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GithubType implements VersionType
{
    private string $branch;

    private string $revision;

    public function __construct(string $srcDir, private HttpClientInterface $client, private SerializerInterface $serializer, private LoggerInterface $logger)
    {
        $gitDir = dirname($srcDir).'/prevarisc';

        $this->branch = $this->getCurrentBranch($gitDir);
        $this->revision = $this->getLastRevision($gitDir);
    }

    private function getCurrentBranch(string $gitDir): string
    {
        try {
            $process = new Process(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $gitDir);
            $process->mustRun();
            $branch = trim($process->getOutput());

            return '' !== $branch ? $branch : 'indisponible';
        } catch (\Exception) {
            return 'indisponible';
        }
    }

    private function getLastRevision(string $gitDir): string
    {
        try {
            $process = new Process(['git', 'rev-list', '--all', '--max-count=1'], $gitDir);
            $process->mustRun();
            $sha1 = trim($process->getOutput());

            return '' !== $sha1 ? $sha1 : 'indisponible';
        } catch (\Exception) {
            return 'indisponible';
        }
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
            // @fixme: modifier par main une fois le dépôt migration basculé en dépôt principal
            $githubApiResponse = $this->client->request('GET', VersionBuilder::REPO_API_URL.'/git/refs/heads/2.5', [
                'timeout' => 3,
            ])->getContent();
        } catch (\Exception $exception) {
            $this->logger->notice("La vérification des commits sur le dépôt n'a pas pu être effectuée : {message}", [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return true;
        }

        $githubApiResponseToDto = $this->serializer->deserialize($githubApiResponse, GithubApi::class, 'json');

        return $this->revision === $githubApiResponseToDto->object()->sha();
    }
}
