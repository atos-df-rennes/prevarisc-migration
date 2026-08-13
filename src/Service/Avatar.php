<?php

namespace App\Service;

use App\Utils\PathNormalizer;

final class Avatar
{
    /** @var string */
    private $basePath;

    public const SMALL_DIRECTORY = 'small';

    public const SMALL_WIDTH = 25;

    public const MEDIUM_DIRECTORY = 'medium';

    public const MEDIUM_WIDTH = 150;

    public const LARGE_DIRECTORY = 'large';

    public const LARGE_WIDTH = 224;

    public function __construct(string $realDataPath)
    {
        $this->basePath = PathNormalizer::join($realDataPath, 'uploads', 'avatars');
    }

    public function path(string $directory, int $userId): string
    {
        return PathNormalizer::join($this->basePath, $directory, (string) $userId.'.jpg');
    }
}
