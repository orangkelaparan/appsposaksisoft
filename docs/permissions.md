# Permissions

Roles provide a convenient baseline, while individual capabilities are stored as permissions and checked server-side before sensitive mutations.

| Role | Operational scope |
| --- | --- |
| Super Administrator | Full access, including settings, users, roles, audit, master data, inventory, purchase, sales, and reports. |
| Business Owner | Dashboard, sales, purchasing, inventory, business performance, and financial reports; no system-critical configuration. |
| Manager | Store sales, inventory, purchasing, returns, customers, suppliers, and staff performance reports. |
| Cashier | POS, customer selection, payments, own sales, receipts, and assigned register; no stock adjustment or configuration. |
| Inventory Staff | Product reference data, stock receiving, adjustments, transfers, counts, and warehouse operations. |
| Purchasing Staff | Suppliers, purchase orders, receiving, and purchase return operations. |
| Accountant / Finance | Sales, purchasing, payment, tax, expense, reconciliation, cash, and financial reports. |
| Auditor | Read-only access to sales, purchasing, inventory, payments, activity history, and reports. |

## Permission catalogue

| Module | Permissions |
| --- | --- |
| Dashboard | `dashboard.view` |
| Products | `products.view`, `products.create`, `products.update`, `products.delete`, `products.import`, `products.export` |
| Inventory | `inventory.view`, `inventory.adjust`, `inventory.transfer`, `inventory.count` |
| Sales | `sales.view`, `sales.create`, `sales.update`, `sales.cancel`, `sales.return`, `sales.refund`, `sales.discount`, `sales.void` |
| Payments | `payments.create`, `payments.refund` |
| Purchasing | `purchases.view`, `purchases.create`, `purchases.approve`, `purchases.receive` |
| Customers | `customers.view`, `customers.create`, `customers.update`, `customers.delete` |
| Suppliers | `suppliers.view`, `suppliers.create` |
| Reports | `reports.view`, `reports.export` |
| Administration | `users.manage`, `roles.manage`, `settings.manage`, `audit.view` |

The current implementation seeds all permissions to Super Administrator. As new role-management screens are extended, assign only the least privilege necessary for each store function.
