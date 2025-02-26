<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AvatarExtension extends AbstractExtension
{
    /** @var string */
    private $dataPath;

    /** @var string */
    private $realDataPath;

    /** @var string */
    private $imageTag;

    public function __construct(string $dataPath, string $realDataPath)
    {
        $this->dataPath = $dataPath;
        $this->realDataPath = $realDataPath;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('avatar', [$this, 'avatar']),
        ];
    }

    /**
     * Retourne l'avatar d'un utilisateur.
     */
    public function avatar(int $id, string $size = 'small', ?string $styleAttributes = null): void
    {
        $src = $this->dataPath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, [
            'uploads',
            'avatars',
            $size
        ]);
        $filePath = $this->realDataPath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, [
            'uploads',
            'avatars',
            $size,
            $id.'.jpg'
        ]);

        $this->imageTag = '<img ';
        $this->addSrc($src, $filePath, $id);
        $this->addStyle($styleAttributes);
        $this->imageTag .= ' alt="Avatar" />';

        echo $this->imageTag;
    }

    private function addSrc(string $src, string $filePath, int $id): void
    {
        $this->imageTag .= ' src="' . $src;

        $imgId = file_exists($filePath) ? $id : 'default';

        $this->imageTag .= DIRECTORY_SEPARATOR . $imgId . '.jpg"';
    }

    private function addStyle(string $styleAttributes): void
    {
        if (null !== $styleAttributes) {
            $this->imageTag .= ' style="' . $styleAttributes . '"';
        }
    }
}
