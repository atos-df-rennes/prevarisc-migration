<?php

namespace App\Dto;

final class Document
{
    public function __construct(private string $filename, private string $filepath)
    {
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function filepath(): string
    {
        return $this->filepath;
    }
}
