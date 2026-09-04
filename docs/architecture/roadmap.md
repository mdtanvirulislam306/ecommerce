# Roadmap

Phased delivery. Architecture stays stable. **Agents own the Active queue** — do not wait for the product owner to say “next” when unchecked items remain.

Canonical business rules: [business-vision.md](business-vision.md) (includes progressive complexity).

---

## Active queue (agent owns this)

Work **top to bottom**. After finishing an item: mark `[x]`, then immediately start the next unchecked item. Only stop on hard block or empty queue.

See also: `.cursor/rules/agent-continue.mdc`.

### Close module gaps (no ModulePage leftovers)

- [x] Inventory: stock transfer (warehouse↔warehouse) + remove transfer ModulePage
- [x] Sales: quotations CRUD + convert to order; invoices from confirmed orders; payments; returns; credit notes; reports overview
- [x] Purchase: supplier groups; receive hub; returns; payments; reports overview
- [x] CRM: lead sources; segments; calendar view; CRM reports; wire create shortcuts
- [x] Accounting: GL, cashbook, AR/AP summaries, expenses/income, bank accounts+txns, P&L, balance sheet, cash flow; drop unused advanced nav or implement
- [x] POS: open sessions list, session history, cash management, returns, reports
- [x] Commerce: overview; promotions; coupons; loyalty program/points; referral; shipping couriers/shipments (lean but real CRUD)
- [x] Ecommerce: store dashboard/settings/SEO; theme stubs with real pages; CMS pages+menus; coupons; shipping; checkout settings; reports
- [x] Marketing: overview; email/SMS campaign channels pages wired; segments; promotions/coupons/loyalty/referral/reports lean CRUD
- [x] Hrm: departments, designations, attendance, leave, payroll, salary structure, HR reports (real tables+CRUD)
- [x] Support: my tickets, unassigned, categories, canned responses, reports
- [x] Settings module: general, business profile, users/roles list, numbering, tax, currency, payment methods, shipping methods, localization, audit log list
- [x] Reports module: cross-module overview + sales/purchase/inventory/crm/ecommerce/pos/accounting/hr dashboards (query real data)
- [x] Workflow module: workflows, approval requests/policies, automation rules, scheduled tasks, logs (lean CRUD)
- [x] Tasks module: my/all tasks, calendar, create/complete
- [x] Notifications module: center, templates, channel prefs
- [x] Files module: media library + documents
- [x] Inventory: batches, serial numbers, stock valuation, inventory reports
- [x] Nav: every sidebar link → real route; ModulePage catch-all removed

### Previously completed (archive)

- [x] Phase 3 Billing / ModuleManager / upgrade UX
- [x] Progressive complexity defaults + flags + simple product price/stock
- [x] Phase 4 Sales/Inventory observability
- [x] POS barcode + thermal; Hrm/Support scaffolds; Marketing campaigns stub

---

## Phase 0 — Architecture (done)

- [x] Stack and pattern decisions
- [x] Docs under `docs/architecture/`
- [x] Project Cursor skills for architecture, modules, coding
- [x] Progressive complexity documented (business-vision + skills + rules)

## Phase 1 — Scaffold (done)

- [x] Laravel project via Composer
- [x] Vue 3 + Inertia + Vite (Breeze)
- [x] Sanctum session auth baseline
- [x] `app/Core` module kernel (`ModuleManager`, middleware, discovery)
- [x] Example Catalog module scaffold
- [x] MySQL `.env` for Laragon
- [x] Root tooling: Pint available via Laravel; Vite build OK

## Phase 2 — Core product features (done)

See [business-vision.md](business-vision.md) for NexCore domain order. PIM detail: [products-pim.md](products-pim.md).

- [x] Product master (simple + variant)
- [x] Categories (primary + additional), brands, units & conversions
- [x] Attributes (variant vs informational), variants, SKU
- [x] Collections, product families (optional grouping)
- [x] Lifecycle: draft → pending → approved → active → archived
- [x] Product approval workflow
- [x] Commerce: price lists, customer group pricing, tier pricing, history
- [x] Ecommerce: publication, storefront presentation, product reviews
- [x] Catalog overview, import/export, product settings
- [x] Inventory: warehouses, stock levels, movements, adjustments + `StockAvailability` contract
- [x] Sales orders: price snapshots via `PriceResolver`, confirm fulfills stock, cancel restocks
- [x] Purchase: suppliers, POs, approve → receive → `StockAvailability::receive`
- [x] CRM: leads pipeline, customers (Commerce groups), activities
- [x] Accounting: COA, double-entry journals, Sales/Purchase listeners, trial balance
- [x] Ecommerce cart / checkout / online orders (COD) + `/shop` storefront
- [x] POS: terminal, registers/sessions, cash sales → stock + cash journals

Each feature lands as Core or a module following [patterns.md](patterns.md) and [modules.md](modules.md).

## Out of scope until requested

- Full UI design implementation (design provided separately)
- Microservices
- Repository layer by default
- Third-party admin panel packages as the core admin
- Billing provider charge integration (Stripe/etc.) until explicitly requested — plans/subscriptions data model comes first
- Optional JSON API stubs (Flutter/mobile)
- Redis cache/queues (load-proven need)

## How to kick off an agent session

Paste (or say):

```text
Drain the Active queue in docs/architecture/roadmap.md.
Follow business-vision + progressive complexity + module patterns.
After each item mark [x] and continue. Don’t ask what’s next.
Stop only on hard block or empty queue.
```
