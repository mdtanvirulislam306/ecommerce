# Frontend

Vue 3 runs **inside** Laravel via **Inertia.js** and **Vite**. No separate SPA for v1.

## Why Inertia

- Matches “Vue inside Laravel”
- No duplicated REST API for the admin/store UI
- Shared auth session (Sanctum cookie/session)
- Partial reloads keep lists and filters fast

JSON API can be added later for mobile or public integrations without changing the module model.

## Folder map

```text
resources/
  js/
    app.js                 # Inertia app bootstrap
    Pages/                 # Core / shared pages (auth, errors)
    Layouts/               # AppLayout, GuestLayout
    Components/            # Shared UI only
    Composables/
modules/
  Catalog/
    Resources/js/Pages/    # Catalog Inertia pages
      Products/
        Index.vue
        Show.vue
```

Resolve module pages via a Vite alias or Inertia page resolver that checks `modules/*/Resources/js/Pages` then `resources/js/Pages`.

## Page naming

Inertia page names:

- Core: `Auth/Login`, `Dashboard`
- Module: `Catalog/Products/Index` (mapped from module Pages)

Keep one page component per screen. Extract shared UI into `resources/js/Components`.

## Layouts

- One primary authenticated layout (nav driven by `enabledModules`)
- Guest layout for login/register
- Module pages use the shared layout; they do not ship their own app shell

## Shared Inertia props

From HandleInertiaMiddleware (or equivalent):

| Prop | Purpose |
|------|---------|
| `auth.user` | Current user |
| `enabledModules` | Array of module codes for nav/gates |
| `flash` | Success/error messages |

## Forms and navigation

- Prefer Inertia `<Form>` / `useForm` for writes
- Use partial reloads (`only: [...]`) for filters, tabs, tables
- Avoid fetching ad-hoc JSON from controllers for the same UI Inertia already serves

## Module UI visibility

```js
// Nav item shown only if module enabled
enabledModules.includes('catalog')
```

Never rely on UI hiding alone — server middleware still enforces the gate.

## Design

UI design is provided separately. Frontend code must follow the delivered design system when implementation starts. Do not invent a parallel design language.

## Performance

- Code-split pages via Vite/Inertia defaults
- Lazy-load heavy components when needed
- No global UI kit mega-bundle unless the design requires a specific library
- Keep shared Components small and reusable
