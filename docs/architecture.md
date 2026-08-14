# Architecture

## Overview

AksiSoft POS is a Laravel 13 full-stack application that renders server-owned Blade views and uses small asynchronous JSON calls for cashier-critical actions. The architecture prioritizes **transaction integrity**, **stock traceability**, and **server-side authorization** over client-side convenience.

| Layer | Responsibility |
| --- | --- |
| Routes and middleware | HTTP routing, CSRF protection, authentication gate, rate limiting, JSON response negotiation. |
| Controllers | Request validation, permission boundary, view composition, and delegation to services. |
| Services | Document numbers, sales, inventory movements, purchasing/receiving, register closing, and audit writes. |
| Database | Normalized master and transaction tables with foreign keys, indexes, fixed-precision decimal values, and historical records. |
| Views | Blade dashboard/back-office pages, a dense POS workspace, and print-safe receipt template. |

## Service boundaries

`SaleService` owns the sale transaction. It computes stored totals, creates an immutable sale and its lines, reduces inventory through `InventoryService`, creates the payment record, and adds an audit record within the same database transaction. A failure in stock validation or any dependent write rolls back the complete operation.

`InventoryService` locks the warehouse/product stock row, validates the post-movement quantity, updates the stock balance, and writes `inventory_ledgers`. No cashier sale or receiving flow modifies stock without a corresponding ledger entry.

`PurchaseService` creates approved purchase orders and supports receiving only outstanding quantities. Receiving posts to the inventory ledger and recalculates an average-cost-ready stock record. `DocumentNumberService` locks and increments per-document sequences so invoices and receipts remain traceable.

## Request flow

```text
Browser POS → CSRF-protected checkout endpoint → SaleService transaction
  → lock product & inventory row → validate stock → sale + sale_items
  → payment → inventory ledger → audit log → commit → receipt URL
```

## Scale and extension points

The schema includes companies, stores, warehouses, registers, user-store assignments, product variants, customer groups, taxes, document sequences, and purchasing documents. This makes multi-outlet deployment native even when the initial business runs only one outlet. Long-running report exports, imports, notifications, or PDF jobs can use Laravel’s database queue without changing the sale path.
