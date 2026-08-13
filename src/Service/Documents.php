<?php

namespace App\Service;

use App\Dto\Document;
use App\Utils\PathNormalizer;
use Symfony\Component\Finder\Finder;

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
}
