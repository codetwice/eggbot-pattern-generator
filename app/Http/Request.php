<?php

namespace App\Http;

class Request
{
    public function __construct(
        private string $method,
        private string $path,
        private array $query,
        private array $request,
        private array $files,
        private array $server
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }

    public function query(): array
    {
        return $this->query;
    }

    public function body(): array
    {
        return $this->request;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);

        return $value !== null && $value !== '';
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->all(), array_flip($keys));
    }

    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && ($this->files[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
    }

    public function file(string $key): ?UploadedFile
    {
        if (! $this->hasFile($key)) {
            return null;
        }

        return new UploadedFile($this->files[$key]);
    }
}
