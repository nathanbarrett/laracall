<?php declare(strict_types=1);

namespace NathanBarrett\LaraCall;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use NathanBarrett\LaraCall\Commands\LaraCallCommand;

class LaraCallServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('LaraCall')
            ->hasConfigFile('laracall')
//            ->hasViews()
//            ->hasMigration('create_laracall_table')
            ->hasCommand(LaraCallCommand::class);
    }
}
