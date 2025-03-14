<?php

namespace App\Service;

use App\Dto\Document;
use Symfony\Component\Finder\Finder;

final class Documents {
    /** @var string */
    private $dataPath;

    /** @var string */
    private $realDataPath;

    /** @var string */
    private $path = DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'documents'.DIRECTORY_SEPARATOR;

    public function __construct(string $dataPath, string $realDataPath) {
        $this->dataPath = $dataPath.$this->path;
        $this->realDataPath = $realDataPath.$this->path;
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
            ->in($this->realDataPath . $idCommission)
            ->ignoreVCS(true)
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
