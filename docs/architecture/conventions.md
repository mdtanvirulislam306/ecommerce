# Conventions

Consistent naming and structure across Core and every module.

## Language

- **Code**: English (classes, methods, DB, routes, comments in source)
- **Chat/docs with the team**: Bangla or English is fine

## PHP naming

| Kind | Convention | Example |
|------|------------|---------|
| Module folder | PascalCase | `Catalog` |
| Module code | snake-ish lowercase | `catalog` |
| Controller | PascalCase + Controller | `ProductController` |
| FormRequest | Verb + noun | `StoreProductRequest` |
| Service | Noun + Service | `ProductService` |
| Model | Singular PascalCase | `Product` |
| Contract | Interface suffix | `InventoryCheckerInterface` |

Namespaces:

```text
Modules\Catalog\Http\Controllers
Modules\Catalog\Services
Modules\Catalog\Models
App\Core\Module
App\Core\Contracts
```

(Wire PSR-4 in `composer.json` when scaffolding.)

## Routes

- Prefix and name by module: `catalog.products.index`
- Group with `auth` + `module:{code}`
- Prefer resource routes for CRUD; explicit routes for actions

## Database

- Table names: plural snake_case (`products`, `order_items`)
- Module migrations in `modules/{Name}/Database/migrations`
- Index foreign keys and common filter columns
- Prefer soft deletes only when the domain needs restore/history
- Avoid hard FKs that make uninstalling a module catastrophic; document ownership clearly

## Responses

- Web UI: Inertia render or redirect with flash
- Validation: FormRequest → 422 via Inertia/Laravel defaults
- Module locked: 403 or redirect to subscription/upgrade

## File placement checklist

New feature in an existing module:

1. Migration (if schema)
2. Model
3. Service method
4. FormRequest
5. Controller action
6. Route
7. Vue page / component

New feature that is subscription-gated as its own product slice → new module (see [modules.md](modules.md)).

## Git / PR habits

- Small, module-scoped changes when possible
- Do not mix unrelated module refactors in one PR
- No secrets in commits (`.env`, keys)

## Performance checklist

- [ ] No N+1 (eager load or selective columns)
- [ ] Lists paginated
- [ ] Heavy work queued
- [ ] No unused packages
- [ ] Module gate checked on server, not only in Vue
