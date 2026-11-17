<?php

namespace App\Twig;

use App\Service\Avatar;
use App\Utils\PathNormalizer;
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
        $src = PathNormalizer::join($this->dataPath, 'uploads', 'avatars', $size);
        $filePath = PathNormalizer::join($this->realDataPath, 'uploads', 'avatars', $size, ($id ?? 0).'.jpg');

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

        $this->imageTag .= '/'.$imgId.'.jpg"';
    }

    private function addStyle(?string $styleAttributes): void
    {
        if (null !== $styleAttributes) {
            $this->imageTag .= ' style="'.$styleAttributes.'"';
        }
    }
}
