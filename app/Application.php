<?php

namespace App;

use App\Exceptions\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Routing\Router;
use Throwable;

class Application
{
    private static ?self $instance = null;

    private Router $router;

    /** @var array<string, mixed> */
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->router = new Router($this);
        self::$instance = $this;
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;
        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function handle(Request $request): Response
    {
        try {
            return $this->router->dispatch($request);
        } catch (HttpException $e) {
            return Response::make($e->getMessage(), $e->getStatusCode());
        } catch (Throwable $e) {
            return Response::make('Server Error', 500);
        }
    }

    public function make(string $class): object
    {
        return new $class();
    }
}
