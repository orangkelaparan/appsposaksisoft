# Database Design

All monetary columns use `DECIMAL(19,4)`. Product and contact master data support soft deletion; completed financial records are retained for auditability.

| Domain | Principal tables | Purpose and key relationships |
| --- | --- | --- |
| Organization | `companies`, `stores`, `warehouses`, `registers`, `register_sessions` | A company has stores; stores have warehouses and registers; sessions bind a cashier to a register. |
| Access | `users`, `roles`, `permissions`, `user_roles`, `role_permissions`, `user_stores` | RBAC baseline plus granular permission mapping and store assignment. |
| Catalogue | `categories`, `brands`, `units`, `taxes`, `products`, `product_variants` | Product master data. `products.sku` and optional `products.barcode` are unique. |
| Relationships | `customer_groups`, `customers`, `suppliers` | Customer selection during sale and supplier selection during purchasing. |
| Inventory | `inventory_stocks`, `inventory_ledgers` | Current warehouse balance plus append-only movement history. Unique key: `(warehouse_id, product_id)`. |
| Purchasing | `purchase_orders`, `purchase_order_items`, `purchase_receipts`, `purchase_receipt_items` | Ordered and received quantities are retained independently, supporting partial delivery. |
| Sales | `sales`, `sale_items`, `payments`, `sale_returns`, `sale_return_items` | Immutable sale snapshot, payment allocation, controlled return trace. |
| Finance and control | `cash_movements`, `expenses`, `audit_logs`, `system_settings`, `document_sequences` | Cash control, expenses, protected activity history, settings, and unique documents. |

## Inventory invariant

`inventory_stocks` is a current balance, not the source of truth by itself. Every change is paired with an `inventory_ledgers` row that contains movement type, signed quantity, before/after values, unit cost, reference type/id, user, and timestamp. Sales and purchase receiving use row locks so concurrent requests cannot oversell the same stock balance.

## Critical indexes

The migration indexes product SKU/name, barcode uniqueness, sales store/date, inventory product/warehouse/date, and document numbers. Foreign keys apply deletion policies appropriate to historical use: master references commonly use `nullOnDelete`, while transactional dependencies are restricted or cascaded only at child-line level.

## ERD summary

```text
Company → Store → Warehouse → InventoryStock ← Product
Store → Register → RegisterSession ← User
Supplier → PurchaseOrder → PurchaseOrderItem → PurchaseReceiptItem
Customer → Sale → SaleItem → Payment
SaleItem → SaleReturnItem → SaleReturn
Product + Warehouse → InventoryLedger
User → AuditLog
```
