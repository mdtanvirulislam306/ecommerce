# Architecture Documentation

Modular ecommerce platform built with **Laravel + Vue 3 (Inertia) + MySQL**.

## Goals

- Simple, optimized, easy-to-maintain code
- Same pattern everywhere
- Module-based features for subscription unlock
- Module isolation: disabling one module must not break others
- Lightweight and fast — no unnecessary layers or packages

## Stack (locked)

| Layer | Choice |
|-------|--------|
| Backend | Laravel 13 |
| Frontend | Vue 3 + Inertia.js + Vite |
| Database | MySQL |
| Auth | Laravel Sanctum (session for Inertia) |
| Modules | Custom module kernel (not nwidart) |
| Pattern | Service layer + thin controllers (no Repository) |
| API | Inertia only for v1; JSON later only if needed |

Scaffold (Phase 1) is installed. See root [README.md](../../README.md).

## How to read

1. [business-vision.md](business-vision.md) — NexCore positioning, domain ownership, **progressive complexity**
2. [roadmap.md](roadmap.md) — **Active queue** (what agents build next) + phase history
3. [overview.md](overview.md) — system map
4. [patterns.md](patterns.md) — Service layer rules
5. [modules.md](modules.md) — module structure and subscription gates
6. [frontend.md](frontend.md) — Inertia + Vue layout
7. [conventions.md](conventions.md) — naming and coding standards
8. [products-pim.md](products-pim.md) — product master catalog (PIM) architecture

## Product assumption

SaaS-style modular ecommerce: one codebase; shops unlock features via subscription. The module kernel works the same for a single-store app with paid add-ons — only tenancy tables differ later.

**Simple shop first:** default path is one store, one Retail price list, one Main warehouse. Advanced multi-price / multi-warehouse UI is opt-in — see [business-vision.md](business-vision.md#progressive-complexity-simple-shop-first).
