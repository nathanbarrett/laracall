<?php declare(strict_types=1);

namespace NathanBarrett\LaraCall\Concerns;

trait UsefulEnum
{
    public static function values(): array
    {
        $reflection = new \ReflectionClass(static::class);

        return collect($reflection->getConstants())->values()->toArray();
    }
}
