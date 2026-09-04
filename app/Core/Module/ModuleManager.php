<?php

namespace App\Core\Module;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Billing\Services\PlanService;

class ModuleManager
{
    /** @var array<string, ModuleManifest>|null */
    private ?array $manifests = null;

    /**
     * @return array<string, ModuleManifest>
     */
    public function all(): array
    {
        return $this->manifests ??= $this->discover();
    }

    public function get(string $code): ?ModuleManifest
    {
        return $this->all()[$code] ?? null;
    }

    public function enabled(string $code): bool
    {
        $manifest = $this->get($code);

        if ($manifest === null) {
            return false;
        }

        if ($manifest->isCore) {
            return true;
        }

        if (! $this->plansReady()) {
            return true;
        }

        return in_array($code, $this->planAllowedCodes(), true);
    }

    /**
     * @return list<string>
     */
    public function enabledCodes(): array
    {
        return array_values(array_map(
            fn (ModuleManifest $manifest) => $manifest->code,
            array_filter($this->all(), fn (ModuleManifest $m) => $this->enabled($m->code)),
        ));
    }

    /**
     * @return list<array{code: string, name: string, description: string, is_core: bool, enabled: bool}>
     */
    public function catalogForAdmin(): array
    {
        return collect($this->all())->map(fn (ModuleManifest $m) => [
            'code' => $m->code,
            'name' => $m->name,
            'description' => $m->description,
            'is_core' => $m->isCore,
            'enabled' => $this->enabled($m->code),
        ])->values()->all();
    }

    /**
     * @return list<string>
     */
    private function planAllowedCodes(): array
    {
        try {
            return app(PlanService::class)->enabledModuleCodesFromSubscription();
        } catch (\Throwable) {
            return array_keys($this->all());
        }
    }

    private function plansReady(): bool
    {
        try {
            return Schema::hasTable('plans')
                && Schema::hasTable('subscriptions');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, ModuleManifest>
     */
    private function discover(): array
    {
        $basePath = base_path('modules');

        if (! File::isDirectory($basePath)) {
            return [];
        }

        $manifests = [];

        foreach (File::directories($basePath) as $directory) {
            $jsonPath = $directory.DIRECTORY_SEPARATOR.'module.json';

            if (! File::exists($jsonPath)) {
                continue;
            }

            /** @var array<string, mixed> $data */
            $data = json_decode(File::get($jsonPath), true, 512, JSON_THROW_ON_ERROR);
            $manifest = ModuleManifest::fromArray($data, $directory);
            $manifests[$manifest->code] = $manifest;
        }

        ksort($manifests);

        return $manifests;
    }
}
