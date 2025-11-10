<?php

namespace App\Routing;

use App\Application;
use App\Exceptions\HttpException;
use App\Http\Request;
use App\Http\Response;

class Router
{
    /** @var array<string, Route[]> */
    private array $routes = [];

    /** @var array<string, Route> */
    private array $handlerIndex = [];

    public function __construct(private Application $app)
    {
    }

    /** @param callable|array{0: class-string, 1: string} $handler */
    public function get(string $pattern, $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    /** @param callable|array{0: class-string, 1: string} $handler */
    public function post(string $pattern, $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    /** @param callable|array{0: class-string, 1: string} $handler */
    private function addRoute(string $method, string $pattern, $handler): void
    {
        $route = new Route($method, $pattern, $handler);
        $this->routes[$method][] = $route;

        $handlerKey = $this->handlerKey($handler);
        if ($handlerKey) {
            $this->handlerIndex[$handlerKey] = $route;
        }
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $candidates = $this->routes[$method] ?? [];
        foreach ($candidates as $route) {
            $match = $this->match($route->pattern, $request->path());
            if ($match === null) {
                continue;
            }

            return $this->callHandler($route->handler, $match['parameters'], $request);
        }

        throw new HttpException(404);
    }

    public function urlFor(array $handler, array $parameters = []): ?string
    {
        $key = $this->handlerKey($handler);
        if (! $key || ! isset($this->handlerIndex[$key])) {
            return null;
        }

        $route = $this->handlerIndex[$key];
        $path = $route->pattern;
        foreach ($parameters as $name => $value) {
            $path = str_replace('{' . $name . '}', urlencode((string) $value), $path);
        }

        $path = preg_replace('/\{[^\}]+\}/', '', $path) ?? $path;

        $query = array_diff_key($parameters, $this->patternParameters($route->pattern));
        if (! empty($query)) {
            $path .= (str_contains($path, '?') ? '&' : '?') . http_build_query($query);
        }

        return $path;
    }

    private function patternParameters(string $pattern): array
    {
        preg_match_all('/\{([^\}]+)\}/', $pattern, $matches);
        $params = [];
        foreach ($matches[1] ?? [] as $name) {
            $params[$name] = true;
        }

        return $params;
    }

    private function match(string $pattern, string $path): ?array
    {
        $regex = preg_replace('/\{([^\}]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (! preg_match($regex, $path, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return ['parameters' => $params];
    }

    /** @param callable|array{0: class-string, 1: string} $handler */
    private function handlerKey($handler): ?string
    {
        if (is_array($handler) && count($handler) === 2) {
            return $handler[0] . '@' . $handler[1];
        }

        return null;
    }

    /** @param callable|array{0: class-string, 1: string} $handler */
    private function callHandler($handler, array $parameters, Request $request): Response
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $instance = $this->app->make($class);
            $arguments = [];
            $reflection = new \ReflectionMethod($class, $method);
            foreach ($reflection->getParameters() as $parameter) {
                $name = $parameter->getName();
                $type = $parameter->getType();
                if ($type && $type->getName() === Request::class) {
                    $arguments[] = $request;
                } elseif (array_key_exists($name, $parameters)) {
                    $arguments[] = $parameters[$name];
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();
                } else {
                    $arguments[] = null;
                }
            }

            $result = $instance->$method(...$arguments);
        } else {
            $result = $handler($request);
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result) || is_object($result)) {
            return Response::json($result);
        }

        return Response::make((string) $result);
    }
}
