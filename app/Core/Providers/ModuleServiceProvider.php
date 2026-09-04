<?php

namespace App\Core\Providers;

use App\Core\Module\ModuleManager;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class);
    }

    public function boot(ModuleManager $modules): void
    {
        foreach ($modules->all() as $manifest) {
            if (! $modules->enabled($manifest->code)) {
                continue;
            }

            $provider = $this->resolveProviderClass($manifest->path, $manifest->name);

            if ($provider !== null) {
                $this->app->register($provider);
            }
        }
    }

    private function resolveProviderClass(string $path, string $name): ?string
    {
        $class = "Modules\\{$name}\\Providers\\{$name}ServiceProvider";

        if (class_exists($class)) {
            return $class;
        }

        $file = $path.DIRECTORY_SEPARATOR.'Providers'.DIRECTORY_SEPARATOR."{$name}ServiceProvider.php";

        if (! File::exists($file)) {
            return null;
        }

        require_once $file;

        return class_exists($class) ? $class : null;
    }
}
