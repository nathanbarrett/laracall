<?php declare(strict_types=1);

namespace NathanBarrett\LaraCall\Data;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollectionInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use NathanBarrett\LaraCall\Enums\RouteMethod;
use NathanBarrett\LaraCall\Exceptions\LaraCallException;
use Spatie\LaravelData\Data;

class RouteCallContext extends Data
{
    public ?Route $route;

    private ?RouteCollectionInterface $routes = null;

    public ?Authenticatable $actingAsEntity = null;

    public ?string $populatedRouteUri;

    private array $queryParams = [];

    /**
     * @throws LaraCallException
     */
    public function __construct(
        public ?string $name = null,
        public ?string $routeName = null,
        public ?string $routeUri = null,
        public ?RouteMethod $routeMethod = null,
        public array $parameters = [],
        public array $headers = [],
        public array $cookies = [],
        public null|string|array $body = null,
        public bool $withoutMiddleware = true,
        public null|int|string $actingAsIdentifier = null,
        public string $accept = 'application/json',
        public string $contentType = 'application/json',
    ) {
        $this->hydrate();
    }

    /**
     * @throws LaraCallException
     */
    public function isCallable($hydrate = false): bool
    {
        if ($hydrate) {
            $this->hydrate();
        }

        return isset($this->route);
    }

    /**
     * @throws LaraCallException
     */
    private function hydrate(): void
    {
        if ($this->routeName || $this->routeUri) {
            $this->route = $this->resolveRoute();
            $this->routeName = $this->route->getName();
            $this->routeUri = $this->route->uri();
            $this->routeMethod = $this->resolveRouteMethod($this->route);
            $this->generatePopulatedUriAndParameters($this->route);
            $this->normalizeRequestBody();
        }

        $this->actingAsEntity = $this->resolveActingAsEntity();
    }

    /**
     * @throws LaraCallException
     */
    public function toRequest(): Request
    {
        if (!$this->isCallable()) {
            throw LaraCallException::routeContextNotCallable();
        }

        return Request::create(
            uri: $this->populatedRouteUri,
            method: $this->routeMethod->value,
            parameters: $this->queryParams,
            cookies: $this->cookies,
            server: array_merge(
                [
                    'HTTP_ACCEPT' => $this->accept,
                    'CONTENT_TYPE' => 'application/json',
                ],
                $this->parseHeaders()
            ),
            content: $this->resolveBody(),
        );
    }

    private function resolveBody(): ?string
    {
        if (!$this->body) {
            return null;
        }
        if (is_array($this->body)) {
            return json_encode($this->body);
        }

        return $this->body;
    }

    private function resolveRouteMethod(Route $route): RouteMethod
    {
        $specified = true;
        if (!$this->routeMethod) {
            $specified = false;
            $this->routeMethod = RouteMethod::GET;
        }

        $allowedMethods = $route->methods;
        if ($specified) {
            if (!in_array(strtoupper($this->routeMethod->value), $allowedMethods)) {
                throw LaraCallException::routeMethodNotAllowed($route->getName() ?? $route->uri(), $this->routeMethod->value);
            }
            return $this->routeMethod;
        }

        return RouteMethod::from(strtoupper($route->methods[0]));
    }

    private function resolveRoute(): Route
    {
        if ($this->routeName) {
            $route = $this->routeCollection()->getByName($this->routeName);
            if (!$route) {
                throw LaraCallException::routeNotFound($this->routeName);
            }

            return $route;
        }

        $uri = Str::startsWith($this->routeUri, '/') ? Str::after($this->routeUri, '/') : $this->routeUri;
        /** @var Route $route */
        foreach ($this->routeCollection()->getRoutes() as $route) {
            if ($route->uri === $uri) {
                if ($this->routeMethod && !in_array($this->routeMethod->value, $route->methods)) {
                    continue;
                }
                return $route;
            }
        }

        throw LaraCallException::routeNotFound($this->routeUri);
    }

    private function routeCollection(): RouteCollectionInterface
    {
        if (!isset($this->routes)) {
            $this->routes = RouteFacade::getRoutes();
        }

        return $this->routes;
    }

    private function resolveActingAsEntity(): ?Authenticatable
    {
        if (!$this->actingAsIdentifier) {
            return null;
        }

        $idColumns = config('laracall.acting_as_identifiers', ['id', 'email']);

        if ($customEntity = config('laracall.acting_as_entity')) {
            /** @var Authenticatable|Model $model */
            $model = app($customEntity);
            foreach ($idColumns as $column) {
                if ($entity = $model::query()->where($column, $this->actingAsIdentifier)->first()) {
                    return $entity;
                }
            }
            throw LaraCallException::customActingAsEntityNotFound($customEntity, $this->actingAsIdentifier);
        }

        foreach ($idColumns as $column) {
            /** @var Authenticatable $entity */
            if ($entity = Auth::getProvider()->retrieveByCredentials([$column => $this->actingAsIdentifier])) {
                return $entity;
            }
        }

        throw LaraCallException::authUserNotFound($this->actingAsIdentifier);
    }

    private function generatePopulatedUriAndParameters(Route $route): void
    {
        $uri = $route->uri();
        $routeParams = $route->parameterNames();

        foreach ($this->parameters as $key => $value) {
            if (in_array($key, $routeParams)) {
                $uri = str_replace("{{$key}}", $value, $uri);
                continue;
            }

            $this->queryParams[$key] = $value;
        }

        $this->populatedRouteUri = $uri;
    }

    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->headers as $header) {
            if (str_contains($header, '=')) {
                [$key, $value] = explode('=', $header, 2);
                $headers['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
            }
        }

        return $headers;
    }

    private function normalizeRequestBody(): void
    {
        if (!$this->body || is_array($this->body)) {
            return;
        }

        try {
            $this->body = json_decode($this->body, Str::startsWith($this->body, '{'), 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // Handle the exception if needed
        }
    }
}
