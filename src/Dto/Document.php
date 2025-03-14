<?php

namespace App\Dto;

final class Document {
    /** @var string */
    private $filename;

    /** @var string */
    private $filepath;

    public function __construct(string $filename, string $filepath) {
        $this->filename = $filename;
        $this->filepath = $filepath;
    }

    public function filename(): string {
        return $this->filename;
    }

    public function filepath(): string {
        return $this->filepath;
    }
}
