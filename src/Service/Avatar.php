<?php

namespace App\Service;

use App\Utils\PathNormalizer;

final readonly class Avatar
{
    private string $basePath;

    public const string SMALL_DIRECTORY = 'small';

    public const int SMALL_WIDTH = 25;

    public const string MEDIUM_DIRECTORY = 'medium';

    public const int MEDIUM_WIDTH = 150;

    public const string LARGE_DIRECTORY = 'large';

    public const int LARGE_WIDTH = 224;

    public function __construct(string $realDataPath)
    {
        $this->basePath = PathNormalizer::join($realDataPath, 'uploads', 'avatars');
    }

    public function path(string $directory, int $userId): string
    {
        return PathNormalizer::join($this->basePath, $directory, $userId.'.jpg');
    }
}
