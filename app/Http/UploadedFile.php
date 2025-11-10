<?php

namespace App\Http;

class UploadedFile
{
    public function __construct(private array $payload)
    {
    }

    public function getMimeType(): ?string
    {
        return $this->payload['type'] ?? null;
    }

    public function move(string $directory, string $filename): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $target = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
        if (! move_uploaded_file($this->payload['tmp_name'], $target)) {
            rename($this->payload['tmp_name'], $target);
        }
    }
}
