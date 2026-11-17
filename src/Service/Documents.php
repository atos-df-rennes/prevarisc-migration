<?php

namespace App\Service;

use App\Dto\Document;
use App\Utils\PathNormalizer;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final readonly class Documents
{
    public function __construct(private string $documentsDataPath, private string $documentsRealDataPath)
    {
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
            ->in(PathNormalizer::join($this->documentsRealDataPath, (string) $idCommission))
            ->ignoreVCS(true)
            ->name('*.odt')
            ->sortByName();

        if (!$finder->hasResults()) {
            return [];
        }

        foreach ($finder as $file) {
            $documents[] = new Document(
                $file->getFilename(),
                PathNormalizer::join($this->documentsDataPath, (string) $idCommission, $file->getFilename())
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
        if (!ctype_digit($idCommission) || '' === $filename || str_contains($filename, '\\')) {
            return null;
        }

        foreach ($this->reportTemplates($idCommission) as $file) {
            if ($file->getFilename() !== $filename) {
                continue;
            }

            $resolvedPath = $file->getRealPath();
            $resolvedBase = realpath(PathNormalizer::normalize($this->documentsRealDataPath));
            if (false === $resolvedPath || false === $resolvedBase) {
                return null;
            }

            // Finder can return symbolic links: their target must remain inside the library.
            $path = PathNormalizer::normalize($resolvedPath);
            $base = PathNormalizer::normalize($resolvedBase);
            $isWithin = '\\' === DIRECTORY_SEPARATOR
                ? 0 === strncasecmp($path, $base, strlen($base))
                : str_starts_with($path, $base);

            return $isWithin ? $resolvedPath : null;
        }

        return null;
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function reportTemplates(string $idCommission): iterable
    {
        $path = PathNormalizer::join($this->documentsRealDataPath, $idCommission);
        if (!is_dir($path)) {
            return [];
        }

        return new Finder()->files()->in($path)->depth('== 0')->name('*.odt')->sortByName();
    }
}
