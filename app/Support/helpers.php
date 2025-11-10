<?php

use App\Application;
use App\Exceptions\HttpException;
use App\Http\Response;
use App\Support\Collection;

function app(?string $abstract = null)
{
    $app = Application::getInstance();
    if ($app === null) {
        throw new \RuntimeException('Application has not been bootstrapped.');
    }

    if ($abstract === null) {
        return $app;
    }

    return $app->make($abstract);
}

function config(string $key, mixed $default = null): mixed
{
    return app()->config($key, $default);
}

function response(string $content = '', int $status = 200): Response
{
    return Response::make($content, $status);
}

function json(mixed $payload, int $status = 200): Response
{
    return Response::json($payload, $status);
}

function collect(mixed $items): Collection
{
    return Collection::make($items);
}

function abort_unless(bool $condition, int $status, string $message = ''): void
{
    if (! $condition) {
        throw new HttpException($status, $message);
    }
}

function storage_path(string $path = ''): string
{
    $base = __DIR__ . '/../../storage';
    return rtrim($base . '/' . ltrim($path, '/'), '/');
}

function view(string $name, array $data = []): Response
{
    return Response::make(render_view($name, $data));
}

function render_view(string $name, array $data = []): string
{
    $path = __DIR__ . '/../../resources/views/' . str_replace('.', '/', $name) . '.php';
    if (! file_exists($path)) {
        throw new \RuntimeException("View [{$name}] not found.");
    }

    extract($data);
    ob_start();
    include $path;

    return ob_get_clean();
}

function asset(string $path): string
{
    return '/' . ltrim($path, '/');
}

function action(array $handler, array $parameters = []): string
{
    $url = app()->router()->urlFor($handler, $parameters);
    if ($url === null) {
        throw new \RuntimeException('Unable to resolve action URL.');
    }

    return $url;
}

function env(string $key, mixed $default = null): mixed
{
    $environment = getenv($key);
    if ($environment !== false) {
        return $environment;
    }

    static $values = null;
    if ($values === null) {
        $values = [];
        $path = __DIR__ . '/../../.env';
        if (file_exists($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (! str_contains($line, '=')) {
                    continue;
                }
                [$envKey, $envValue] = array_map('trim', explode('=', $line, 2));
                $values[$envKey] = trim($envValue, "\"'");
            }
        }
    }

    return $values[$key] ?? $default;
}
