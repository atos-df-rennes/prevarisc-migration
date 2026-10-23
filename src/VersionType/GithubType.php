<?php

namespace App\VersionType;

use App\Builder\VersionBuilder;
use App\Dto\GithubApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GithubType implements VersionType
{
    private string $branch;

    private string $revision;

    public function __construct(string $srcDir, private HttpClientInterface $client, private SerializerInterface $serializer, private LoggerInterface $logger)
    {
        $this->branch = $this->getCurrentBranch($srcDir);
        $this->revision = $this->getLastRevision($srcDir);
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
            $process = new Process(['git', 'rev-parse', 'HEAD'], $gitDir);
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
            $branch = in_array($this->branch, ['HEAD', 'indisponible'], true) ? 'main' : $this->branch;
            $branchRef = explode('/', $branch)
                    |> (fn($x): array => array_map(rawurlencode(...), $x))
                    |> (fn($x): string => implode('/', $x));
            $response = $this->client->request('GET', VersionBuilder::REPO_API_URL.'/git/ref/heads/'.$branchRef, [
                'timeout' => 3,
            ]);

            if ('main' !== $branch && Response::HTTP_NOT_FOUND === $response->getStatusCode()) {
                $response = $this->client->request('GET', VersionBuilder::REPO_API_URL.'/git/ref/heads/main', [
                    'timeout' => 3,
                ]);
            }

            $githubApiResponse = $response->getContent();
        } catch (\Exception $exception) {
            $this->logger->notice("La vérification des commits sur le dépôt n'a pas pu être effectuée : ".$exception->getMessage());

            return true;
        }

        $githubApiResponseToDto = $this->serializer->deserialize($githubApiResponse, GithubApi::class, 'json');

        return $this->revision === $githubApiResponseToDto->object()->sha();
    }
}
