<?php

namespace App\Service;

use App\Dto\GithubApi;
use SebastianBergmann\Git\Git;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @phpstan-type GitRevision array{
 *   sha1: string,
 * }
 */
final class Version
{
    private const REPO_URL = 'https://github.com/atos-df-rennes/prevarisc';

    /** @var string */
    private $version;

    /** @var string */
    private $link;

    /** @var bool */
    private $isUpToDate;

    public static function create(): self
    {
        if (false !== getenv('PREVARISC_BRANCH')) {
            return self::fromEnv();
        }

        return self::fromGit();
    }

    private static function fromEnv(): self
    {
        $branch = getenv('PREVARISC_BRANCH');
        $revision = getenv('PREVARISC_REVISION');
        $version = $branch.'.'.$revision;
        $link = '/releases/tag/v'.$version;

        return new self($version, $link, true);
    }

    private static function fromGit(): self
    {
        // @todo: Utiliser le paramètre défini globalement
        $srcDir = dirname(__FILE__, 4);
        $git = new Git($srcDir.'/prevarisc');

        $branch = $git->getCurrentBranch();

        /** @var GitRevision[] */
        $revisions = $git->getRevisions();
        $lastRevision = end($revisions);

        if (false === $lastRevision) {
            throw new \Exception('Aucune révision trouvée.');
        }

        $revision = $lastRevision['sha1'];
        $version = $branch.'@'.substr($revision, 0, 7);
        $link = '/commit/'.$revision;

        // @todo: Faire ça mieux
        $client = HttpClient::create();
        $response = $client->request('GET', 'https://api.github.com/repos/atos-df-rennes/prevarisc/git/refs/heads/'.$branch)->getContent();
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $githubResponse = $serializer->deserialize($response, GithubApi::class, 'json');

        return new self($version, $link, $revision === $githubResponse->object()->sha());
    }

    private function __construct(string $version, string $link, bool $isUpToDate)
    {
        $this->version = $version;
        $this->link = self::REPO_URL.$link;
        $this->isUpToDate = $isUpToDate;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function link(): string
    {
        return $this->link;
    }

    public function isUpToDate(): bool
    {
        return $this->isUpToDate;
    }
}
