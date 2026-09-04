# Modules

Feature modules live under `modules/`. Core kernel lives under `app/Core/`. Modules can be locked behind a subscription.

## Folder layout

```text
app/
  Core/
    Module/
      ModuleManager.php
      ModuleManifest.php
      Middleware/EnsureModuleEnabled.php
    Contracts/                 # cross-module interfaces
    Support/                   # base Service, helpers
  Models/                      # User, Tenant (if any) only
modules/
  Catalog/
    module.json
    Providers/CatalogServiceProvider.php
    Routes/web.php
    Http/Controllers/
    Http/Requests/
    Services/
    Models/
    Database/migrations/
    Resources/js/Pages/
  Cart/
  Order/
```

## module.json

Every module has a manifest:

```json
{
  "name": "Catalog",
  "code": "catalog",
  "version": "1.0.0",
  "description": "Products and categories",
  "is_core": false,
  "dependencies": []
}
```

| Field | Meaning |
|-------|---------|
| `code` | Stable key for gates, plans, middleware (`catalog`) |
| `is_core` | Always enabled; not sold as an add-on |
| `dependencies` | Other module `code`s required if this module is enabled |

Soft dependency checks: enabling a module whose dependency is missing must fail clearly, not cascade into fatal errors elsewhere.

## Isolation rules

1. Do **not** import another module’s Models, Controllers, or concrete Services.
2. Cross-module calls use a **Core Contract** or **events**.
3. Migrations stay inside the module; Core loads providers for enabled modules only.
4. Locked/disabled module → no routes, no menu entries, no Inertia pages.
5. Disabling a module must not break others that did not declare it as a hard dependency.

## ModuleManager

Responsibilities:

- Discover manifests under `modules/*/module.json`
- Resolve enabled set: core modules + subscription-allowed codes
- `enabled(string $code): bool`
- Register ServiceProviders and routes only for enabled modules

Cache the enabled set (config/cache). Invalidate when subscription or plan modules change.

## Middleware: EnsureModuleEnabled

Apply to module route groups:

```php
Route::middleware(['auth', 'module:catalog'])
    ->prefix('catalog')
    ->name('catalog.')
    ->group(base_path('modules/Catalog/Routes/web.php'));
```

If the module is off, abort `403` or redirect to an upgrade/subscription page.

## Subscription gate (skeleton)

Tables (implemented when billing lands):

| Table | Role |
|-------|------|
| `modules` | Registered modules (`code`, `name`, `is_core`) |
| `plans` | Subscription plans |
| `plan_modules` | Plan → allowed module codes |
| `subscriptions` | Active plan per shop/tenant |

`ModuleManager::enabled('catalog')`:

1. `is_core` → true
2. Else check current subscription’s plan modules
3. Else false

Share `enabledModules` via Inertia middleware so Vue can hide nav items.

## ServiceProvider per module

Each module registers:

- Routes (web; later API if needed)
- Migrations path
- Bindings for Contracts it implements
- Optional: Inertia page namespace / Vite alias for its Pages

## Adding a module

See project skill `ecommerce-module` and checklist:

1. Create `modules/{Name}/` tree + `module.json`
2. ServiceProvider + register in Core discovery
3. Routes behind `module:{code}`
4. Controllers → FormRequests → Services → Models
5. Inertia pages under module `Resources/js/Pages`
6. Migrations; no foreign keys that force a disabled module’s tables if avoidable (prefer nullable / soft links documented in events)

## Core modules (tentative)

Final list when features are defined. Likely always-on:

- Auth / account
- Minimal catalog browse (or shop shell)
- Checkout path required for a working store

Paid/example add-ons later: advanced inventory, POS, multi-warehouse, marketing, etc.
