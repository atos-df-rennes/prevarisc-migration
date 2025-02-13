<?php

namespace App\Service;

use SebastianBergmann\Git\Git;

/**
 * @phpstan-type GitRevision array{
 *   sha1: string,
 * }
 */
final class Version
{
    /** @var string */
    private $version;

    /** @var string */
    private $link;

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

        return new self($version, $link);
    }

    private static function fromGit(): self
    {
        $srcDir = dirname(__FILE__, 3);
        $git = new Git($srcDir);

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

        return new self($version, $link);
    }

    private function __construct(string $version, string $link)
    {
        $this->version = $version;
        $this->link = $link;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function link(): string
    {
        return $this->link;
    }
}
