# System Overview

High-level map of the **NexCore** platform (Commerce Operating System: retail + wholesale + ecommerce + ERP modules).

Business positioning, domain ownership, and phased delivery: [business-vision.md](business-vision.md).

## Architecture at a glance

```mermaid
flowchart TB
  subgraph client [Browser]
    VuePages[Vue_Inertia_Pages]
  end

  subgraph laravel [Laravel_App]
    Middleware[Auth_and_ModuleGate]
    Controllers[Thin_Controllers]
    FormRequests[FormRequests]
    Services[Services]
    Models[Eloquent_Models]
    Core[Core_ModuleManager]
  end

  subgraph modules [Feature_Modules]
    Catalog[Catalog]
    Cart[Cart]
    Order[Order]
    More[Other_Modules]
  end

  subgraph data [MySQL]
    Tables[(Tables)]
  end

  VuePages --> Middleware
  Middleware --> Controllers
  Controllers --> FormRequests
  FormRequests --> Services
  Services --> Models
  Services --> Core
  Controllers --> modules
  Models --> Tables
  Core --> modules
```

## Layers

| Layer | Responsibility |
|-------|----------------|
| Vue + Inertia | UI, forms, partial reloads |
| Middleware | Auth, CSRF, `EnsureModuleEnabled` |
| Controller | HTTP only: call service, return Inertia/redirect |
| FormRequest | Validation |
| Service | Business rules, transactions, events |
| Eloquent Model | Persistence, relations, scopes |
| Core | Module registry, subscription gate, shared base classes |

## Core vs modules

- **`app/Core/`** — always loaded: module manager, middleware, shared support
- **`app/Models/`** — true core models only (e.g. `User`, tenant if any)
- **`modules/{Name}/`** — feature modules (Catalog, Cart, Order, …)

Disabled or unsubscribed modules do not register routes, menus, or Inertia pages.

## Request flow

```mermaid
sequenceDiagram
  participant Browser
  participant Middleware
  participant Controller
  participant Service
  participant Model

  Browser->>Middleware: Inertia request
  Middleware->>Middleware: Auth + ModuleGate
  Middleware->>Controller: Pass if module enabled
  Controller->>Service: Validated data
  Service->>Model: Query / persist
  Model-->>Service: Result
  Service-->>Controller: DTO or model
  Controller-->>Browser: Inertia page or redirect
```

## Cross-module communication

Modules must not import each other’s Models or Controllers.

Allowed:

1. **Contract** in Core, implemented by a module, resolved via container
2. **Domain events** dispatched by one module, listened by another

## Performance stance

- Eager-load deliberately; avoid N+1
- Paginate lists
- Cache module registry (and config); Redis only when needed later
- Queues for email and heavy work only
- Prefer Laravel built-ins over extra packages
