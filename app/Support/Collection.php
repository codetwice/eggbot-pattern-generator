<?php

namespace App\Support;

use Closure;
use IteratorAggregate;
use ArrayIterator;

class Collection implements IteratorAggregate
{
    public function __construct(private array $items)
    {
    }

    public static function make(mixed $items): self
    {
        if ($items instanceof self) {
            return $items;
        }

        if ($items === null) {
            return new self([]);
        }

        if (is_array($items)) {
            return new self($items);
        }

        return new self([$items]);
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    public function contains(callable $callback): bool
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return true;
            }
        }

        return false;
    }

    public function filter(?callable $callback = null): self
    {
        $callback ??= fn ($value) => (bool) $value;
        return new self(array_values(array_filter($this->items, $callback)));
    }

    public function values(): self
    {
        return new self(array_values($this->items));
    }

    public function all(): array
    {
        return $this->items;
    }

    public function firstWhere(string $key, mixed $value): mixed
    {
        foreach ($this->items as $item) {
            if (is_array($item) && ($item[$key] ?? null) === $value) {
                return $item;
            }
        }

        return null;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
