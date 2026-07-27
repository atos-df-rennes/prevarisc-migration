<?php

namespace App\Service;

use App\Dto\Document;
use Symfony\Component\Finder\Finder;

final class Documents
{
    public function __construct(private string $dataPath, private string $realDataPath)
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
            ->in($this->realDataPath.$idCommission)
            ->ignoreVCS(true)
            ->name('*.odt')
            ->sortByName();

        if (!$finder->hasResults()) {
            return [];
        }

        foreach ($finder as $file) {
            $documents[] = new Document(
                $file->getFilename(),
                $this->dataPath.$idCommission.DIRECTORY_SEPARATOR.$file->getFilename()
            );
        }

        return $documents;
    }
}
