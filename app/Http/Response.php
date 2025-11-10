<?php

namespace App\Http;

class Response
{
    private array $headers = [];

    public function __construct(private string $content = '', private int $status = 200)
    {
    }

    public static function make(string $content = '', int $status = 200): self
    {
        return new self($content, $status);
    }

    public static function json(mixed $payload, int $status = 200): self
    {
        $response = new self(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $status);
        $response->header('Content-Type', 'application/json');

        return $response;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function status(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
