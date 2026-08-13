<?php

namespace App\Service;

use App\Dto\Document;
use App\Utils\PathNormalizer;
use Symfony\Component\Finder\Finder;

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
}
