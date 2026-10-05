<?php

namespace App\Service;

use App\Dto\Document;
use App\Utils\PathNormalizer;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class Documents
{
    /** @var string */
    private $dataPath;

    /** @var string */
    private $realDataPath;

    public function __construct(string $documentsDataPath, string $documentsRealDataPath)
    {
        $this->dataPath = $documentsDataPath;
        $this->realDataPath = $documentsRealDataPath;
    }

    /**
     * @return Document[]
     */
    public function findForCommission(int $idCommission): array
    {
        $finder = new Finder();
        $documents = [];

        $finder
            ->files()
            ->in(PathNormalizer::join($this->realDataPath, (string) $idCommission))
            ->ignoreVCS(true)
            ->name('*.odt')
            ->sortByName();

        if (!$finder->hasResults()) {
            return [];
        }

        foreach ($finder as $file) {
            $documents[] = new Document(
                $file->getFilename(),
                PathNormalizer::join($this->dataPath, (string) $idCommission, $file->getFilename())
            );
        }

        return $documents;
    }

    /**
     * @return string[]
     */
    public function listReportTemplates(int $idCommission): array
    {
        $templates = [];
        foreach ($this->reportTemplates((string) $idCommission) as $file) {
            $templates[] = $file->getFilename();
        }

        return $templates;
    }

    public function resolveReportTemplate(string $relativeFile): ?string
    {
        $parts = explode('/', $relativeFile);
        if (2 !== count($parts)) {
            return null;
        }

        [$idCommission, $filename] = $parts;
        if (!ctype_digit($idCommission) || '' === $filename || false !== strpos($filename, '\\')) {
            return null;
        }

        foreach ($this->reportTemplates($idCommission) as $file) {
            if ($file->getFilename() !== $filename) {
                continue;
            }

            $resolvedPath = $file->getRealPath();
            $resolvedBase = realpath(PathNormalizer::normalize($this->realDataPath));
            if (false === $resolvedPath || false === $resolvedBase) {
                return null;
            }

            // Finder can return symbolic links: their target must remain inside the library.
            $path = PathNormalizer::normalize($resolvedPath);
            $base = PathNormalizer::normalize($resolvedBase);
            $isWithin = '\\' === DIRECTORY_SEPARATOR
                ? 0 === strncasecmp($path, $base, strlen($base))
                : 0 === strpos($path, $base);

            return $isWithin ? $resolvedPath : null;
        }

        return null;
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function reportTemplates(string $idCommission): iterable
    {
        $path = PathNormalizer::join($this->realDataPath, $idCommission);
        if (!is_dir($path)) {
            return [];
        }

        return (new Finder())->files()->in($path)->depth('== 0')->name('*.odt')->sortByName();
    }
}
