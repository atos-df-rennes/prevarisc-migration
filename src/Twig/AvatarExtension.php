<?php

namespace App\Twig;

use App\Service\Avatar;
use App\Utils\PathNormalizer;
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
