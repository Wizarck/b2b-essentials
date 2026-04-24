# b2b-essentials

> **B2B essentials for WooCommerce** — B2B customer roles, moderated registration, hidden pricing until login, company fields with NIF/VIES validation. **Company-agnostic, reusable.**

[![License: LGPL-3.0](https://img.shields.io/badge/License-LGPL%203.0-blue.svg)](https://opensource.org/licenses/LGPL-3.0)

## Why this plugin

If you run a WooCommerce store for B2B customers (wholesalers, restaurants, resellers), you typically need:

- A dedicated **B2B customer role** (separate from regular retail customers)
- **Moderated registration** — new B2B accounts stay in `pending` until an admin approves them
- **Hidden pricing** — unauthenticated visitors or pending customers don't see prices
- **Company billing fields** — company name, NIF/CIF/NIE, VAT ID with VIES validation, payment terms
- **Product visibility by role** — some SKUs exclusive to B2B

Existing B2B plugins (like WholesaleX, B2BKing) bundle volume discounts, tiered pricing, 20+ registration fields you don't need. `b2b-essentials` does **just the essentials** (~400 lines) with a clean extension point if volume pricing becomes needed.

## What it does NOT do

- Volume discounts or pricing tiers (add a separate `b2b-volume-discounts` plugin if needed)
- Per-customer custom pricing (hook point provided, implementation left to tenant config)
- Multi-tier company hierarchies
- 20-field registration forms (use a form builder if you need this)

## Modules

- **Roles**: Creates `b2b_customer` and `b2b_customer_pending` WordPress roles
- **Registration**: Moderated signup flow with email notifications to admin
- **Pricing visibility**: Hides prices behind login + approval gate
- **Company fields**: Adds NIF, VAT ID, payment terms to checkout + customer profile
- **NIF validator**: Algorithm validation (mod-11 + letter) for ES NIF/CIF/NIE + VIES SOAP async for EU VAT IDs

## Configuration via filters

No settings page by design. All configuration via filters, ideal for tenant-config plugins:

```php
// Require VIES verification for EU customers
add_filter( 'b2b_essentials_auto_approve_rules', fn() => [
    'require_vies_if_eu' => true,
    'require_algorithm_valid_nif' => true,
    'allowed_email_domains' => null, // any
    'blacklist_nifs' => [],
] );

// Mark a product as B2B-only
update_post_meta( $product_id, '_b2b_product_only', 1 );
```

## Companion plugins

- [wc-facturascripts-sync](https://github.com/Wizarck/wc-facturascripts-sync) — sync WC ↔ FacturaScripts (for Spanish fiscal compliance)

## License

LGPL-3.0-or-later. See [LICENSE](./LICENSE).

## Status

**🚧 Early development**. Scaffold 2026-04-24. Target first release: 2026-07-01.
