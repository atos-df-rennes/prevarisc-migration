<?php

namespace App\Twig;

use App\Service\Avatar;
use Twig\Attribute\AsTwigFunction;

final class AvatarExtension
{
    private ?string $imageTag = null;

    public function __construct(private readonly string $dataPath, private readonly string $realDataPath)
    {
    }

    /**
     * Retourne l'avatar d'un utilisateur.
     */
    #[AsTwigFunction(name: 'avatar')]
    public function avatar(?int $id = null, string $size = Avatar::SMALL_DIRECTORY, ?string $styleAttributes = null): void
    {
        $src = $this->dataPath.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, [
            'uploads',
            'avatars',
            $size
        ]);
        $filePath = $this->realDataPath.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, [
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

    private function addSrc(string $src, string $filePath, ?int $id): void
    {
        $this->imageTag .= ' src="'.$src;

        $imgId = file_exists($filePath) ? $id : 'default';

        $this->imageTag .= DIRECTORY_SEPARATOR.$imgId.'.jpg"';
    }

    private function addStyle(?string $styleAttributes): void
    {
        if (null !== $styleAttributes) {
            $this->imageTag .= ' style="'.$styleAttributes.'"';
        }
    }
}
