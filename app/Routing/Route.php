<?php

namespace App\Routing;

class Route
{
    /** @param callable|array{0: class-string, 1: string} $handler */
    public function __construct(
        public string $method,
        public string $pattern,
        public $handler
    ) {
    }
}
