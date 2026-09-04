# Product Information Management (PIM)

Product is the **central master entity** for Catalog, Inventory, Purchase, Sales, POS, Wholesale, and Ecommerce. One product is created once and consumed everywhere — never duplicated per channel.

## Core principle

```text
                    ┌── POS
                    │
                    ├── Ecommerce
Product (Master) ───┼── Wholesale / Retail
                    ├── Purchase
                    ├── Inventory
                    └── Sales
```

PIM owns **identity and catalog data**. It does **not** own stock quantities, channel presentation rules, or transaction history.

## Product model

### Simple vs variant

```text
Product
├── Simple Product     (single SKU, no variants)
└── Variant Product    (attributes generate combinations)
       ├── Variant 1
       ├── Variant 2
       └── Variant 3
```

### Product family (optional)

Product Family is **optional grouping**, not the master product.

Example: family `T-Shirt` groups many sellable products or variant matrices for merchandising — it does not replace `Product`.

### What belongs on Product (master)

| Area | Examples | Notes |
|------|----------|--------|
| Basic information | name, description, internal code | |
| Classification | primary category, additional categories | see Categories |
| Brand | Nike, Adidas | master data, many products |
| Unit | Piece, Kg, Carton | with conversions |
| Attributes | Color, Size, Material | variant vs informational |
| Variants | SKU matrix | for variant products only |
| Media | images, video | |
| SEO | meta title, slug | PIM data; ecommerce may override presentation |
| Lifecycle status | draft → active → archived | business validity |
| Publication | draft / published | ecommerce visibility — **separate** from approval |

### What does NOT belong on Product

| Data | Owner module |
|------|----------------|
| Stock quantity | Inventory |
| Warehouse allocation | Inventory |
| Retail / wholesale / dealer prices as columns | Commerce (Price Lists) |
| Customer reviews | Ecommerce |
| Order line snapshots | Sales |
| Purchase receive lines | Purchase |

**Never hardcode pricing columns on `products`:**

```text
❌ retail_price, wholesale_price, dealer_price, distributor_price
```

Use Price Lists + Customer Groups (Commerce module).

## Categories

- **Primary category** — exactly one (reporting, default breadcrumb).
- **Additional categories** — many (collections, campaigns, cross-navigation).

Example:

- Primary: `Men > Clothing > T-Shirt`
- Additional: `Summer Collection`, `New Arrival`, `Best Seller`

## Brands

Separate master table. Product references `brand_id`. One brand → many products.

## Units & conversion

Master units: Piece, Kg, Gram, Liter, Box, Carton, Dozen, Meter.

**Unit conversion** (wholesale ERP):

```text
1 Carton = 12 Pieces
Purchase: 10 Cartons → Inventory: 120 Pieces
```

## Attributes

Two types:

| Type | Purpose | Example |
|------|---------|---------|
| **Variant attribute** | Generates SKU combinations | Color, Size |
| **Informational attribute** | Spec sheet only | Material, Country of Origin |

Variant generation example:

```text
Product: T-Shirt
Color: Red, Blue
Size: M, L, XL
→ Red-M, Red-L, Red-XL, Blue-M, Blue-L, Blue-XL
```

Each variant owns: SKU, barcode, weight, image, status. Stock and price resolution attach at variant/SKU level via other domains.

## Pricing (Commerce domain)

```text
Product / Variant
      ↓
Price List (Retail, Wholesale, Dealer, VIP, …)
      ↓
Customer Group
      ↓
Resolved price
```

**Tier / quantity pricing** (wholesale):

```text
1–9 pcs   = ৳100
10–49 pcs = ৳95
50–99 pcs = ৳90
100+ pcs  = ৳85
```

Pricing engine resolves by quantity at order time. Implemented in **Commerce**, not Catalog.

## Collections

Marketing/catalog grouping — not the same as category tree.

Examples: Summer Collection, New Arrivals, Best Sellers, Ramadan Collection, Clearance.

Products can belong to **multiple collections**.

## Approval vs publication

Two independent axes:

### Product lifecycle (business validity)

```text
Draft → Pending Review → Approved → Active → Archived
```

### Ecommerce publication (storefront visibility)

```text
Not Published → Published → Unpublished
```

Approved ≠ Published. A product can be business-valid but not on the website.

## Domain boundaries

### PIM (Catalog module)

Product name, SKU, description, images, attributes, variants, brand, category, units, SEO fields, lifecycle status.

### Ecommerce module

Show on website?, featured?, sort order, homepage visibility, **product reviews**, publication status overrides for storefront.

### Inventory module

Warehouse, on-hand, reserved, available, batch, serial. PIM never stores stock counts.

### Purchase

Supplier → PO → receive → inventory. Product does not embed purchase transactions.

### Sales

Order items store **snapshots** (product_id, variant_id, sku, name, unit_price, qty, tax) so historical orders stay immutable if the master product changes.

## Entity relationship (summary)

```text
Category ──────────────┐
Brand ────────────────►│ Product ──► Variants ──► SKU
Product Family (opt) ──┘       │
                               ├── Attributes / Media
                               │
         ┌─────────────────────┼─────────────────────┐
         ▼                     ▼                     ▼
    Commerce              Inventory              Sales
  (Price Lists)           (Stock)               (Orders)
```

## Admin navigation

See `resources/js/navigation/modules.js`:

- **Products & Catalog** — PIM screens (products, families, variants, attributes, categories, brands, collections, units, approval, settings).
- **Commerce** — price lists, customer group pricing, tier pricing, promotions.
- **Ecommerce** — product reviews, storefront presentation.

Product Reviews UI shortcut may appear near catalog, but backend domain is **Ecommerce**.

## Implementation notes (Catalog module)

| Entity | Table (planned) | Module |
|--------|-----------------|--------|
| products | `products` | Catalog |
| product_variants | `product_variants` | Catalog |
| product_families | `product_families` | Catalog |
| categories | `categories` | Catalog |
| brands | `brands` | Catalog |
| units | `units` | Catalog |
| unit_conversions | `unit_conversions` | Catalog |
| attributes | `attributes` | Catalog |
| attribute_options | `attribute_options` | Catalog |
| collections | `collections` | Catalog |
| price_lists | `price_lists` | Commerce |
| product_reviews | `product_reviews` | Ecommerce |

Cross-module: Inventory references `product_variant_id` or `sku_id`; Sales order lines snapshot fields; Commerce resolves price via contracts/events.

## Related docs

- [modules.md](modules.md) — module isolation
- [patterns.md](patterns.md) — Service layer
- [roadmap.md](roadmap.md) — build phases
