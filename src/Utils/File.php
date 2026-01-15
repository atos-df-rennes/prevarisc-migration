<?php

namespace App\Utils;

class File
{
    public static function sanitize(string $filename): string
    {
        $sanitizedFilename = basename($filename);

        if (in_array($sanitizedFilename, ['', '.', '..'], true)) {
            throw new \InvalidArgumentException("Le nom du fichier n'est pas correct.");
        }

        return $sanitizedFilename;
    }
}
