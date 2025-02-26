<?php declare(strict_types=1);

namespace NathanBarrett\LaraCall\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Prompts\Concerns\Colors;
use NathanBarrett\LaraCall\Data\RouteCallContext;
use NathanBarrett\LaraCall\Enums\RouteMethod;
use NathanBarrett\LaraCall\Exceptions\LaraCallException;
use NathanBarrett\LaraCall\Helpers\JsonInputDecoder;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

class LaraCallCommand extends Command
{
    use Colors;

    public $signature = "route:call
                            {route? : route name or path to call}
                            {--method= : the HTTP method to use, defaults to the path's method or GET}
                            {--auth= : the user or entity id or email to authenticate as}
                            {--param=* : the parameters to pass to the route}
                            {--header=* : the headers to pass to the route}
                            {--cookie=* : the cookies to pass to the route}
                            {--body=* : the body to pass to the route}
                            {--output-raw : output the raw body}
                            {--without-middleware : call the route without middleware}
                            {--context-name= : adds a name to the request for easier identification later}";

    public $description = 'Calls any route in your Laravel app with custom context';

    public function handle(): int
    {
        try {
            return $this->callRoute();
        } catch (LaraCallException $e) {
            dump('LaraCallException', $e->getMessage());
            $this->outputLaraCallException($e);
            return self::FAILURE;
        } catch (\Throwable $e) {
            dump('Exception', $e->getMessage(), $e->getTrace()[0]);
            $this->outputExceptionThrown($e);
            return self::FAILURE;
        }
    }

    /**
     * @throws LaraCallException
     */
    private function callRoute(): int
    {
        $this->appEnvironmentCheck();
        $this->appDebugCheck();

        if ($this->argument('route')) {
            return $this->handleCommandLineCall();
        }

        return self::SUCCESS;
    }

    private function appEnvironmentCheck(): void
    {
        $specifiedEnvironments = config('laracall.allowed_environments', ['*']);
        if (!is_array($specifiedEnvironments)) {
            $specifiedEnvironments = [$specifiedEnvironments];
        }

        if (in_array('*', $specifiedEnvironments) || in_array(app()->environment(), $specifiedEnvironments)) {
            return;
        }

        throw LaraCallException::environmentNotAllowed();
    }

    private function appDebugCheck(): void
    {
        if (config('laracall.debug', true)) {
            config(['app.debug' => true]);
        }
    }

    /**
     * @throws LaraCallException
     */
    private function handleCommandLineCall(): int
    {
        $callContext = $this->buildRouteContextFromCommandLine();

        $this->bootstrapHttpKernel();

        $request = $callContext->toRequest();

        if (!in_array($callContext->routeMethod->value, ['GET', 'HEAD', 'OPTIONS'])) {
            $session = Session::getFacadeRoot();
            $session->start();
            if ($token = csrf_token()) {
                $request->headers->set('X-CSRF-TOKEN', $token);
            }
        }

        $this->laravel->instance('request', $request);

        if ($callContext->actingAsEntity) {
            Auth::login($callContext->actingAsEntity);
            $request->setUserResolver(fn() => Auth::user());
        }

        info("Calling route '{$callContext->populatedRouteUri}' with method '{$callContext->routeMethod->value}'...");
        try {
            $start = microtime(true);

            $router = app(Router::class);
            $response = $this->option('without-middleware') ?
                $this->dispatchWithoutMiddleware($callContext->route, $request) :
                $router->dispatch($request);

            $finish = microtime(true);

            if ($exception = $response->exception ?? null) {
                $this->outputExceptionThrown($exception);
                return self::FAILURE;
            }
            $elapsedMs = round(($finish - $start) * 1000, 2);
            $this->outputResponse($response, $elapsedMs);
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->outputExceptionThrown($e);
            return self::FAILURE;
        }
    }

    /**
     * @throws LaraCallException
     */
    private function buildRouteContextFromCommandLine(): RouteCallContext
    {
        $route = $this->argument('route');
        $isRouteUri = Str::startsWith($route, '/');

        return new RouteCallContext(
            name: $this->option('context-name'),
            routeName: $isRouteUri ? null : $route,
            routeUri: $isRouteUri ? $route : null,
            routeMethod: RouteMethod::tryFrom(strtoupper($this->option('method') ?? '')),
            parameters: $this->optionToArray('param'),
            headers: $this->optionToArray('header'),
            cookies: $this->optionToArray('cookie'),
            body: $this->optionToArray('body'),
            withoutMiddleware: $this->option('without-middleware'),
            actingAsIdentifier: $this->option('auth'),
        );
    }

    private function dispatchWithoutMiddleware(Route $route, Request $request): mixed
    {
        $action = $route->getAction('controller');

        if (!$action) {
            throw new \Exception("Route does not have a controller action defined.");
        }

        [$controllerClass, $method] = explode('@', $action);

        // Resolve the controller instance from the container
        $controller = app($controllerClass);

        // Call the controller method with the request
        return app()->call([$controller, $method], $this->resolveControllerMethodParameters($route, $request));
    }

    private function resolveControllerMethodParameters(Route $route, Request $request): array
    {
        $parameters = [];
        $routeParams = $route->parameterNames();

        foreach ($routeParams as $param) {
            // Use parameters from the request if available
            $value = $request->route($param) ?? $request->input($param);
            if ($value !== null) {
                $parameters[$param] = $value;
            }
        }

        return $parameters;
    }

    private function bootstrapHttpKernel(): void
    {
        $app = $this->laravel;
        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

        // Boot the kernel to initialize middleware and other HTTP-related services
        $kernel->bootstrap();
    }

    private function outputExceptionThrown(\Throwable $e): void
    {
        if ($e instanceof LaraCallException) {
            $this->outputLaraCallException($e);
            return;
        }

        error("Exception thrown: " . get_class($e));
        error("Message: " . $e->getMessage() . "\nStatus Code: " . $e->getCode());

        if ($trace = $e->getTrace()) {
            $this->line("Trace:");
            foreach ($trace as $item) {
                $file = $item['file'] ?? null;
                $line = $item['line'] ?? null;
                if (!$file || !$line) {
                    continue;
                }
                if (str_contains($item['file'], '/vendor/')) {
                    $this->line($this->dim("  {$item['file']} ({$item['line']})"));
                    continue;
                }
                $this->line("  {$item['file']} ({$item['line']})");
            }
        }
    }

    private function outputLaraCallException(LaraCallException $e): void
    {
        error("There was an error with your request: " . $e->getMessage());
    }

    private function outputResponse($response, float|int|null $elapsedMs = null): void
    {
        $statusCode = $this->getResponseStatusCode($response);
        $statusColorMethod = $statusCode >= 200 && $statusCode < 300 ? 'green' : 'red';
        info("Response: {$this->$statusColorMethod((string)$response->status())}" . ($elapsedMs ? " in ({$elapsedMs}ms)" : ''));
        info("Response Headers:");
        foreach ($response->headers->all() as $key => $values) {
            $this->line("  {$key}: " . implode(', ', $values));
        }

        info("Response Content:");
        if ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse) {
            $this->line($this->italic("binary file response"));
            return;
        }

        if ($response instanceof Response || $response instanceof JsonResponse) {
            $content = $response->getContent();
            if ($content === false || $content === "") {
                $this->line($this->italic("empty"));
                return;
            }

            if ($this->option('output-raw')) {
                $this->line($content);
                return;
            }

            try {
                $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                $this->line(json_encode($decoded, JSON_PRETTY_PRINT));
            } catch (\JsonException $e) {
                $this->line($content);
            }
        }
    }

    private function getResponseStatusCode($response): int
    {
        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            return $response->getStatusCode();
        }

        return 0;
    }

    private function optionToArray(string $option): array
    {
        $result = [];

        if (empty($this->option($option))) {
            return $result;
        }

        foreach ($this->option($option) as $item) {
            if (
                (Str::startsWith($item, '{') && Str::endsWith($item, '}')) ||
                (Str::startsWith($item, '[') && Str::endsWith($item, ']'))
            ) {
                $values = JsonInputDecoder::fixAndDecode($item);
                if (is_array($values)) {
                    $result = array_merge($result, $values);
                    continue;
                }
            }

            if (str_contains($item, '=')) {
                [$key, $value] = explode('=', $item, 2);
                $result[$key] = $value;
                continue;
            }

            $result[$item] = null;
        }

        return $result;
    }
}
