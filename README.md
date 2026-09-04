# Ecommerce

Modular ecommerce platform: **Laravel 13 + Vue 3 (Inertia) + MySQL**.

## Quick start (Laragon)

1. Start MySQL in Laragon
2. Install & migrate:

```bash
composer install
npm install
php artisan migrate --seed
npm run build
php artisan serve --port=8080
```

3. Admin login: **http://127.0.0.1:8080/admin/login**
   - Email: `admin@admin.com`
   - Password: `password`

Register is disabled. All admin routes use the `/admin/*` prefix.

## Stack

| Layer | Choice |
|-------|--------|
| Backend | Laravel 13 |
| Frontend | Vue 3 + Inertia.js + Vite (Breeze) |
| Auth | Sanctum session |
| DB | MySQL (`ecommerce`) |
| Modules | Custom kernel in `app/Core` + `modules/` |

## Architecture docs

See **[docs/architecture/README.md](docs/architecture/README.md)**.

## Module kernel

- `app/Core/Module/ModuleManager.php` — discover + enable check
- `module` middleware alias → `EnsureModuleEnabled`
- Inertia shares `enabledModules`
- Example module: `modules/Catalog` (placeholder products page)

## Principles

- Service layer, thin controllers — no Repository by default
- Module isolation for future subscription unlock
- Lightweight; same pattern everywhere
