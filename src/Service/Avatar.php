<?php

namespace App\Service;

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
        $this->basePath = $realDataPath.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'avatars';
    }

    public function path(string $directory, int $userId): string
    {
        return $this->basePath.DIRECTORY_SEPARATOR.$directory.DIRECTORY_SEPARATOR.$userId.'.jpg';
    }
}
