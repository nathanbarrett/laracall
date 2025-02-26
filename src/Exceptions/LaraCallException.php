<?php declare(strict_types=1);

namespace NathanBarrett\LaraCall\Exceptions;

class LaraCallException extends \Exception
{
    public static function environmentNotAllowed(): self
    {
        return new self('Not allowed to run in this environment');
    }

    public static function routeNotFound(string $routeName): self
    {
        return new self("Route with name `{$routeName}` not found");
    }

    public static function routeMethodNotAllowed(string $routeName, ?string $method): self
    {
        return new self("Route with name `{$routeName}` does not allow method `{$method}`");
    }

    public static function customActingAsEntityNotFound(string $entityClass, mixed $identifier): self
    {
        return new self("Entity `{$entityClass}` with identifier `{$identifier}` not found");
    }

    public static function authUserNotFound(mixed $identifier): self
    {
        return new self("Auth with identifier `{$identifier}` not found");
    }

    public static function routeContextNotCallable(): self
    {
        return new self('Route context is not callable');
    }
}
