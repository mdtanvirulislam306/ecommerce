# NexCore Business Vision & Domain Logic

Source conversation (reviewed, not followed blindly): [ChatGPT — ERP E-commerce System Plan](https://chatgpt.com/share/6a985104-3864-83ee-918f-385095bfd839)

This doc is the **project’s adopted business/architecture learning**. When ChatGPT (or any external advice) conflicts with this file + `docs/architecture/*`, **this repo wins**.

---

## Product positioning (locked)

| Decision | Choice |
|----------|--------|
| Brand | **NexCore** (Commerce Operating System — not “boring ERP” branding) |
| Market | **Retail + Wholesale + Ecommerce** |
| Product type | **ERP + Ecommerce Builder** (one platform: ops + storefront) |
| Shape | **Modular monolith**, SaaS-ready, subscription-gated modules |
| Admin UI | Vue 3 + **Inertia** + Tailwind (session auth) |
| Mobile | Flutter later via **API** — not day-1 for admin |
| Database | MySQL |

Vision one-liner:

> One product created once in PIM; consumed by Inventory, Purchase, Sales, POS, Wholesale, and Ecommerce — never duplicated per channel.

---

## Phased delivery (business order)

Revenue and master data first. Do **not** build every ERP module at once.

### Phase A — Core commerce spine (current focus)

1. **Catalog (PIM)** — product master, variants, attributes, brands, categories, units, approval
2. **Commerce** — price lists, customer groups, quantity tiers, price history, promotions later
3. **Ecommerce** — publication, storefront presentation, reviews, online orders later
4. **Inventory** — stock movements, warehouses (ops heart)
5. **Sales / Purchase** — orders with line snapshots; PO → receive → stock
6. **CRM** — lead → customer pipeline
7. **Accounts** — double-entry journals (BD ERP quality bar)

### Phase B — Operations

- POS (barcode, thermal, multi-branch)
- HRM / payroll
- Support tickets + messaging channels

### Phase C — Advanced

- Marketing automation (email/SMS/WhatsApp)
- BI / forecasting
- AI assistant features
- Full Stripe / auto-billing UX (manual tenant provisioning is live)

Defer until needed: Redis, Meilisearch, S3, GraphQL, heavy DDD ceremony.

---

## Domain map (who owns what)

```text
Catalog (PIM)     → identity: name, SKU, media, attributes, lifecycle
Commerce          → money rules: price lists, groups, tiers, promos
Ecommerce         → storefront: publish, featured, homepage, reviews, themes
Inventory         → stock: warehouse, on-hand, reserved, movements, batch/serial
Purchase          → inbound: suppliers, PO, receive
Sales / POS       → outbound: orders, invoices, snapshots
Accounts          → books: COA, journals, double-entry
CRM               → relationships: leads, customers, activities
HRM / Support     → people & tickets
```

### Hard rules

1. **No price columns on `products`** (`retail_price`, `wholesale_price`, …). Resolve via Commerce `PriceResolver`.
2. **No stock quantities on `products`**. Inventory owns counts via movements.
3. **Lifecycle ≠ publication**. Draft→…→Active vs Not published→Published→Unpublished.
4. **Order lines snapshot** product/sku/name/unit_price/qty — history stays correct if master changes.
5. **Cross-module**: Contracts in `app/Core/Contracts` or domain events — never import another module’s Models/Controllers/Services.
6. Disabling a module must not fatal other modules.

---

## Progressive complexity (simple shop first)

Not every tenant needs warehouses, multi-store, or multi-price. **Complexity is optional** — the engine stays flexible; the UI stays simple by default.

Vision one-liner for UX:

> One shop, one price, one stock number — until the merchant opts into more.

### Defaults on tenant / shop setup

Auto-create (or ensure exists) without forcing configuration screens:

| Default | Purpose |
|---------|---------|
| 1 company / store | Single retail location |
| 1 price list: **Retail** (default) | Commerce owns price; UI can show a single “Selling price” |
| 1 warehouse: **Main** | Inventory owns qty; UI can show a single “Stock qty” when multi-warehouse is off |

Product create in simple mode may collect **name + selling price + stock qty**. Persistence must still go through **Commerce** (`PriceResolver` / default price list item) and **Inventory** (movement or on-hand on Main) — **never** `retail_price` / stock columns on `products`.

### Progressive disclosure

| Mode | Merchant sees | Hidden until enabled / upgraded |
|------|---------------|----------------------------------|
| **Simple retail** | Products, one price, one stock number, sales | Price-list admin UI, warehouses, branches, customer groups, quantity tiers |
| **Growing** | + customer groups / second price list | Multi-warehouse transfers, multi-branch |
| **Full** | Branches, warehouses, wholesale tiers, POS | — |

Prefer:

1. **Module gates** (`module:{code}`) — wholesale, POS, Accounts off → not in nav  
2. **Settings flags** (e.g. `multi_warehouse`, `multi_price`, `multi_branch`) — hide advanced screens while defaults remain  
3. **No parallel product model** — do not simplify by putting price/stock on Catalog products

### Agent / implementation rules

1. Never force warehouse, branch, or price-list screens on the **default** product create/edit flow.
2. Single-field price/stock in the UI is allowed; writes must target Commerce / Inventory domain owners.
3. Enabling advanced features must **not** require re-creating products or migrating price/stock onto `products`.
4. When only one warehouse / one price list exists, resolve it implicitly (default Retail / Main) instead of asking the user to pick.
5. Seeders and onboarding should create the Retail + Main defaults so simple shops work on day one.

---

## SaaS hierarchy (shared MySQL + tenant_id)

```text
Platform → Tenant → Company → Branch → Warehouse
```

**Implemented now (multi-tenant kernel):**

- Shared database with `tenants`, `tenant_domains`, `tenant_module_overrides`
- Host → `ResolveTenantFromHost` → `TenantContext`; Eloquent `BelongsToTenant` global scope
- Platform Super Admin (`is_platform_admin`, `tenant_id = null`) provisions shops at `/platform/tenants`
- Plans stay global; subscriptions and module overrides are per-tenant
- Existing install bootstraps as Tenant **Default** (`slug=default`)
- Email unique per tenant: `unique(tenant_id, email)`
- Suspended tenant or expired subscription → storefront/admin locked (403); platform console remains open
- Cart session keys are prefixed by tenant id

Company/Branch under a tenant remains future work.

---

## Inventory business logic (when we build it)

Inventory is the **ops heart**, not the product master.

Stock is **movement-driven**:

```text
+ Purchase receive, return-in, adjustment-in, transfer-in
− Sale/POS fulfill, damage, transfer-out, return-out
```

Every quantity change writes a `stock_movements` (or equivalent) row. Warehouse-level stock; Sales/POS/Ecommerce consume through Inventory contracts/events (`OrderCreated` → reserve/reduce stock), not direct table writes.

Support later: batch, serial, bundles/composite — only after simple + variant stock works.

---

## Accounting business logic (when we build it)

Use **double-entry** from the start of Accounts:

```text
Sale on credit:  Dr AR / Cr Revenue
Payment:         Dr Cash / Cr AR
```

Sales/POS/Purchase emit events; Accounts listeners post journals. Never bury ledger math inside Sales controllers.

---

## CRM / POS / Ecommerce highlights (adopt)

- **CRM pipeline:** New → Contacted → Qualified → Proposal → Won → Customer
- **POS:** offline-friendly goals, barcode, multi-branch — after Inventory + Sales basics
- **Storefront:** modern conversion UX (stories, featured, one-page checkout, COD, wallet/coupon later) — Catalog/Commerce already separate presentation vs price

---

## Coding pattern (adopted — better than ChatGPT default)

ChatGPT suggested Repository + DTO + Actions + API-first everywhere.

**This repo instead:**

| Layer | Rule |
|-------|------|
| Pattern | Service layer + thin controllers |
| Persistence | Eloquent on Models — **no Repository by default** |
| Validation | FormRequest |
| Admin UI | **Inertia** pages under `modules/*/Resources/js/Pages` |
| API | Add later for Flutter / public storefront — do not dual-build admin as JSON API |
| Modules | `modules/{Name}` + `module.json` + ServiceProvider + `module:` middleware |
| Enums | PHP backed enums with transition rules where lifecycle matters |

See [patterns.md](patterns.md), [modules.md](modules.md), [conventions.md](conventions.md).

---

## What we explicitly reject from the ChatGPT plan

| ChatGPT suggestion | Why we reject / defer |
|--------------------|------------------------|
| `cost_price` / `selling_price` on `products` | Breaks multi-price (retail/wholesale/dealer); Commerce owns pricing |
| `is_featured` / publish fields only on product as Inventory concerns | Ecommerce owns storefront presentation (`product_storefront_settings`) |
| Products living under Inventory module | PIM is Catalog; Inventory is stock only |
| Repository + DTO layers by default | Boilerplate without benefit on Eloquent |
| API-first admin from day 1 | Admin is Inertia; API for mobile later |
| Redis / Meilisearch / S3 day 1 | Add when load or product need proves it |
| Build all ERP modules in parallel | Phase A spine first |
| Status as free-form VARCHAR only | Use PHP enums for controlled transitions; DB stores string values |

Useful ChatGPT ideas we **keep**: modular isolation, events between modules, SaaS hierarchy thinking, movement-based stock, double-entry accounts, retail+wholesale+ecom positioning, NexCore branding, modern admin UX.

---

## Build order reminder (next domains)

After Catalog / Commerce / Ecommerce admin spine:

1. Inventory contracts + stock movements  
2. Sales order + snapshots consuming `PriceResolver`  
3. Purchase → receive → stock  
4. CRM customers shared with Commerce customer groups  
5. Accounts listeners  
6. Storefront + Flutter API  

---

## Related docs

- [overview.md](overview.md) — system map  
- [products-pim.md](products-pim.md) — product master  
- [modules.md](modules.md) — isolation  
- [patterns.md](patterns.md) — Service layer  
- [roadmap.md](roadmap.md) — phases  
