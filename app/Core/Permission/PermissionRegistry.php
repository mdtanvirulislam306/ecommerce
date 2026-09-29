<?php

namespace App\Core\Permission;

use App\Core\Module\Middleware\EnsureModuleEnabled;
use App\Core\Module\ModuleManager;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

/**
 * Derives a permission key for every module route as "{module}.{area}.{ability}", so new routes are
 * protected without registering anything. The area is the second route-name segment; the ability comes
 * from the HTTP verb (view/create/update/delete) or, for custom write actions, the action name itself.
 */
final class PermissionRegistry
{
    public const VIEW = 'view';

    public const CREATE = 'create';

    public const UPDATE = 'update';

    public const DELETE = 'delete';

    public const GENERAL_AREA = 'general';

    /**
     * Two-segment GET route names whose last segment is a listing filter or standard page rather than an area.
     *
     * @var list<string>
     */
    private const MODULE_WIDE_PAGES = [
        'index', 'show', 'create', 'edit', 'overview', 'dashboard', 'all', 'active', 'draft', 'pending',
        'archived', 'approved', 'cancelled', 'confirmed', 'export', 'print', 'product-variants',
        'product-search', 'preview-price',
    ];

    /** @var list<string> */
    private const CREATE_ACTIONS = ['store', 'quick', 'add'];

    /** @var list<string> */
    private const UPDATE_ACTIONS = ['update'];

    /** @var list<string> */
    private const DELETE_ACTIONS = ['destroy', 'remove'];

    /** @var list<array{name: string, uri: string, method: string, module: string, key: string, has_parameters: bool}>|null */
    private ?array $entries = null;

    public function __construct(
        private readonly Router $router,
        private readonly ModuleManager $modules,
    ) {}

    public function keyForRoute(Route $route): ?string
    {
        $module = $this->moduleCode($route);
        $name = $route->getName();

        if ($module === null || $name === null) {
            return null;
        }

        return $this->key($module, $name, $route->methods()[0] ?? 'GET');
    }

    /**
     * @return list<string>
     */
    public function allKeys(?array $moduleCodes = null): array
    {
        return collect($this->entries())
            ->when($moduleCodes !== null, fn ($entries) => $entries->whereIn('module', $moduleCodes))
            ->pluck('key')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Permissions grouped for the role editor, limited to the given modules.
     *
     * @param  list<string>  $moduleCodes
     * @return list<array{code: string, name: string, areas: list<array{code: string, name: string, permissions: list<array{key: string, name: string}>}>}>
     */
    public function catalog(array $moduleCodes): array
    {
        return collect($this->entries())
            ->whereIn('module', $moduleCodes)
            ->groupBy('module')
            ->sortKeys()
            ->map(fn ($entries, string $module) => [
                'code' => $module,
                'name' => $this->modules->get($module)?->name ?? Str::headline($module),
                'areas' => $entries
                    ->groupBy(fn (array $entry) => explode('.', $entry['key'])[1])
                    ->sortKeys()
                    ->map(fn ($areaEntries, string $area) => [
                        'code' => $area,
                        'name' => $area === self::GENERAL_AREA ? 'General' : Str::headline($area),
                        'permissions' => $areaEntries
                            ->pluck('key')
                            ->unique()
                            ->sortBy(fn (string $key) => $this->abilityOrder($key))
                            ->map(fn (string $key) => ['key' => $key, 'name' => $this->abilityLabel($key)])
                            ->values()
                            ->all(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Static admin page paths (e.g. "/admin/crm/leads/all") the user may not open, for hiding navigation.
     *
     * @return list<string>
     */
    public function deniedPagePaths(User $user): array
    {
        if ($user->hasFullShopAccess()) {
            return [];
        }

        return collect($this->entries())
            ->where('method', 'GET')
            ->where('has_parameters', false)
            ->reject(fn (array $entry) => $user->hasPermission($entry['key']))
            ->map(fn (array $entry) => '/'.ltrim($entry['uri'], '/'))
            ->unique()
            ->values()
            ->all();
    }

    private function key(string $module, string $routeName, string $method): string
    {
        $segments = explode('.', $routeName);
        $action = end($segments);
        $isRead = in_array(strtoupper($method), ['GET', 'HEAD'], true);

        return $module.'.'.$this->area($segments, $isRead).'.'.$this->ability($action, $isRead);
    }

    /**
     * @param  list<string>  $segments
     */
    private function area(array $segments, bool $isRead): string
    {
        if (count($segments) >= 3) {
            return $segments[1];
        }

        if (count($segments) === 2 && $isRead && ! in_array($segments[1], self::MODULE_WIDE_PAGES, true)) {
            return $segments[1];
        }

        return self::GENERAL_AREA;
    }

    private function ability(string $action, bool $isRead): string
    {
        if ($isRead) {
            return match ($action) {
                'create' => self::CREATE,
                'edit' => self::UPDATE,
                default => self::VIEW,
            };
        }

        return match (true) {
            in_array($action, self::CREATE_ACTIONS, true) => self::CREATE,
            in_array($action, self::UPDATE_ACTIONS, true) => self::UPDATE,
            in_array($action, self::DELETE_ACTIONS, true) => self::DELETE,
            default => $action,
        };
    }

    private function abilityOrder(string $key): string
    {
        $ability = Str::afterLast($key, '.');
        $position = array_search($ability, [self::VIEW, self::CREATE, self::UPDATE, self::DELETE], true);

        return $position === false ? '9'.$ability : (string) $position;
    }

    private function abilityLabel(string $key): string
    {
        return match ($ability = Str::afterLast($key, '.')) {
            self::VIEW => 'View',
            self::CREATE => 'Create',
            self::UPDATE => 'Edit',
            self::DELETE => 'Delete',
            default => Str::headline($ability),
        };
    }

    private function moduleCode(Route $route): ?string
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            foreach (['module:', EnsureModuleEnabled::class.':'] as $prefix) {
                if (str_starts_with($middleware, $prefix)) {
                    return substr($middleware, strlen($prefix));
                }
            }
        }

        return null;
    }

    /**
     * @return list<array{name: string, uri: string, method: string, module: string, key: string, has_parameters: bool}>
     */
    private function entries(): array
    {
        if ($this->entries !== null) {
            return $this->entries;
        }

        $entries = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            $key = $this->keyForRoute($route);

            if ($key === null) {
                continue;
            }

            $entries[] = [
                'name' => (string) $route->getName(),
                'uri' => $route->uri(),
                'method' => $route->methods()[0] ?? 'GET',
                'module' => (string) $this->moduleCode($route),
                'key' => $key,
                'has_parameters' => $route->parameterNames() !== [],
            ];
        }

        return $this->entries = $entries;
    }
}
