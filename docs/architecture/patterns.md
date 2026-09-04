# Patterns

Canonical pattern for every feature: **Service layer + thin controllers**. No Repository layer.

## Why Service, not Repository

Eloquent already abstracts the database. A Repository on top of Eloquent adds boilerplate without benefit for this app. Business logic lives in Services; persistence stays on Models.

Use a Repository only in a rare case where storage must be swappable (e.g. external catalog API). That is the exception, not the default.

## Request pipeline

```text
Vue Page → Route → Middleware (auth, module gate)
  → Controller → FormRequest → Service → Eloquent Model
  → Inertia response / redirect
```

## Controller rules

Controllers:

- Accept a FormRequest (or validated array)
- Call one Service method
- Return `Inertia::render(...)`, redirect, or JSON error
- Do **not** contain business rules, queries, or transactions

```php
// Good
public function store(StoreProductRequest $request, ProductService $service)
{
    $product = $service->create($request->validated());

    return redirect()->route('catalog.products.show', $product);
}
```

```php
// Bad — logic and queries in controller
public function store(Request $request)
{
    $product = Product::create($request->all());
    // discounts, stock, events...
}
```

## FormRequest rules

- One FormRequest per write action (`Store*`, `Update*`)
- Authorization can live here or in policies; keep it consistent per module
- Return only validated keys the Service needs

## Service rules

Services:

- Own business rules and multi-model workflows
- Use `DB::transaction()` when multiple writes must succeed together
- Dispatch events when other modules may react
- Depend on Eloquent models of **this** module, or Contracts for other modules
- Stay framework-friendly: injectable, no static god classes

```php
final class ProductService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::query()->create($data);
            // module-local side effects
            return $product;
        });
    }
}
```

## Model rules

Models:

- Relations, casts, scopes, accessors
- No orchestration of checkout, subscriptions, or cross-module rules
- Prefer query scopes for reusable filters used by Services

## Contracts (cross-module only)

When Module A needs Module B:

```php
// app/Core/Contracts/InventoryCheckerInterface.php
interface InventoryCheckerInterface
{
    public function available(int $productId, int $qty): bool;
}
```

Implement in the Inventory module; bind in that module’s ServiceProvider. Catalog/Cart inject the interface — never the concrete class from another module.

Prefer events when the caller does not need a return value.

## Anti-patterns (do not)

- Repository + Service + DTO on every CRUD
- Fat controllers
- Modules importing another module’s Models/Controllers
- God Services that span unrelated domains
- Premature microservices or full DDD folders
- Extra packages for problems Laravel already solves

## Testing stance (when tests start)

- Feature tests hit HTTP/Inertia routes
- Unit-test Services with complex rules
- Keep tests aligned with module boundaries
