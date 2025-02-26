<?php

namespace NathanBarrett\LaraCall\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use NathanBarrett\LaraCall\LaraCallServiceProvider;
use NathanBarrett\LaraCall\Tests\TestControllers\TestEntityController;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'NathanBarrett\\LaraCall\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaraCallServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }

    /**
     * Define routes setup.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    protected function defineRoutes($router): void
    {
        $router->get('/messages', [TestEntityController::class, 'index'])->name('messages.index');
        $router->post('/messages', [TestEntityController::class, 'store'])->name('messages.store');
        $router->get('/messages/{id}', [TestEntityController::class, 'show'])->name('messages.show');
        $router->put('/messages/{id}', [TestEntityController::class, 'update'])->name('messages.update');
        $router->delete('/messages/{id}', [TestEntityController::class, 'destroy'])->name('messages.destroy');

        $router->get('/no-body', [TestEntityController::class, 'noBody'])->name('test.no-body');
        $router->get('/no-content', [TestEntityController::class, 'noContent'])->name('test.no-content');

        $router->get('/optional-parameter/{id}/{name?}', [TestEntityController::class, 'optionalParameter'])
            ->name('test.optional-parameter');
    }
}
