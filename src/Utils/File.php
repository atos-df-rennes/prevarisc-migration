<?php

namespace App\Utils;

class File
{
    /**
     * Sanitize a filename by removing any directory components and rejecting invalid names.
     *
     * This method is intended to help prevent simple path traversal attacks by ensuring that
     * only the basename of the provided path is used, and by refusing special values such
     * as an empty string, "." or "..".
     *
     * @param string $filename the original filename or path provided by the caller
     *
     * @return string the sanitized filename basename that can safely be used in file operations
     *
     * @throws \InvalidArgumentException If the resulting filename is empty, ".", or "..".
     */
    public static function sanitize(string $filename): string
    {
        $sanitizedFilename = basename($filename);

        if (in_array($sanitizedFilename, ['', '.', '..'], true)) {
            throw new \InvalidArgumentException("Le nom du fichier n'est pas correct.");
        }

        return $sanitizedFilename;
    }

    /**
     * Sanitize a user-provided display name for safe use in an HTTP Content-Disposition header
     * (e.g. via BinaryFileResponse::setContentDisposition()) or as a ZIP entry name.
     *
     * Unlike self::sanitize(), this method does not treat the input as a path: it is meant for
     * free-text names (e.g. PieceJointe::getNom()) that may legitimately contain spaces or
     * accented characters. However, "/" and "\" must be removed since
     * HeaderUtils::makeDisposition() throws an InvalidArgumentException when the filename
     * contains either of them.
     *
     * @param string $filename the original filename or display name provided by the caller
     *
     * @return string the sanitized filename, safe to use in a Content-Disposition header
     */
    public static function sanitizeForContentDisposition(string $filename): string
    {
        return str_replace(['/', '\\'], '-', $filename);
    }
}
