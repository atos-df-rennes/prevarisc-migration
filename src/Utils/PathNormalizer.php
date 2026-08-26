<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Normalizes file paths to handle configuration with or without trailing slashes.
 * Works consistently on both Windows and Unix systems by normalizing to forward slashes.
 *
 * @todo: use Symfony's Path Component instead.
 */
final class PathNormalizer
{
    /**
     * Normalizes a path by:
     * - Converting backslashes to forward slashes
     * - Removing multiple consecutive slashes
     * - Ensuring a trailing slash
     *
     * @param string $path The path to normalize
     *
     * @return string Normalized path with trailing slash
     */
    public static function normalize(string $path): string
    {
        if ('' === $path) {
            return '/';
        }

        // Convert any backslashes to forward slashes (for Windows consistency)
        $path = str_replace('\\', '/', $path);

        // Preserve a leading double slash, which denotes a UNC network path
        // (e.g. \\server\share, turned into //server/share above). Collapsing
        // it to a single slash would break access to the mounted file server.
        $isUncPath = 0 === strncmp($path, '//', 2);

        // Remove multiple consecutive slashes
        $path = (string) preg_replace('#/+#', '/', $path);

        if ($isUncPath) {
            $path = '/'.$path;
        }

        // Ensure trailing slash
        return rtrim($path, '/').'/';
    }

    /**
     * Joins multiple path segments with proper normalization.
     * Handles trailing/leading slashes in arguments automatically.
     *
     * @param string ...$parts Path segments to join
     *
     * @return string Joined and normalized path (without trailing slash)
     */
    public static function join(string ...$parts): string
    {
        $joined = implode('/', $parts);

        // Normalize but return without trailing slash for join operations
        return rtrim(self::normalize($joined), '/');
    }
}
