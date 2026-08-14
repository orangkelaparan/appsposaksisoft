# MASTER PROMPT — BUILD A COMPLETE PROFESSIONAL POS SYSTEM

You are a senior full-stack software architect, Laravel engineer, database architect, UI/UX designer, DevOps engineer, QA engineer, security engineer, and product designer.

Your task is to **design, implement, test, deploy, and document a production-ready Point of Sale (POS) application** for a real retail/business environment.

Do not create a toy project, demo, mockup, or simplified CRUD application.

Build it as a **real commercial-grade POS platform** with a clean architecture, complete database, proper permissions, transaction integrity, auditability, reporting, inventory management, purchasing, customer management, cashier workflow, and deployment automation.

The application must be designed so it can realistically be used by:

- Retail stores
- Grocery stores
- Mini markets
- Fashion stores
- Electronics stores
- Restaurants/light food businesses
- Multi-cashier stores
- Multi-outlet businesses
- Businesses with inventory and purchasing operations

---

# 1. CORE TECHNOLOGY REQUIREMENTS

Use the following stack unless there is a strong technical reason to improve it:

## Backend

- PHP 8.3+
- Laravel — use the latest stable Laravel release compatible with the server environment after checking the official Laravel documentation
- MySQL 8.x / compatible MariaDB only if necessary
- Laravel Eloquent ORM
- Laravel migrations
- Laravel seeders
- Laravel factories
- Laravel validation
- Laravel policies/gates
- Laravel queues where appropriate
- Laravel scheduler
- Laravel events/listeners where useful
- Laravel notifications
- Laravel filesystem abstraction
- Laravel cache
- Laravel logging

Laravel's current documentation should be checked before implementation rather than assuming an old framework version.

## Frontend

Prefer:

- Blade
- Livewire
- Alpine.js
- Tailwind CSS

The POS screen must feel like a modern commercial POS application, not a traditional Laravel admin panel.

Use AJAX/Livewire interactions where appropriate so cashier operations do not require full-page reloads.

## Database

MySQL.

Use:

- foreign keys
- indexes
- unique constraints
- composite indexes where necessary
- decimal types for money
- proper timestamps
- soft deletes where appropriate
- status columns where appropriate
- database transactions for financial/inventory operations

Never use floating-point numbers for monetary values.

---

# 2. APPLICATION DOMAIN

Primary domain:

https://pos.aksisoft.web.id

The system must support:

- single store
- multiple stores/outlets
- multiple warehouses
- multiple cash registers
- multiple users
- multiple roles
- multiple currencies if designed appropriately
- tax configuration
- discounts
- promotions
- inventory movement
- purchasing
- sales
- returns
- refunds
- customer accounts
- supplier management
- reports
- auditing

Design the database so multi-outlet support is native even if the initial installation only has one outlet.

---

# 3. BRANDING

Create a professional POS brand.

Brand name:

**AksiSoft POS**

Recommended logo concept:

- Main icon: stylized geometric POS terminal combined with a shopping basket/barcode
- Wordmark: "AksiSoft"
- Secondary text: "POS"
- Modern technology/business aesthetic
- Clean geometric shapes
- Professional, trustworthy, scalable
- Must work on desktop, tablet, mobile and receipt printing
- Logo must work in:
  - sidebar
  - login page
  - favicon
  - receipt
  - invoice
  - reports
  - PDF
  - print layout

Suggested visual direction:

- Primary: deep navy / charcoal
- Accent: electric blue or professional indigo
- Positive state: green
- Warning: amber
- Danger: red
- Neutral backgrounds: light gray/white
- Dark mode should be considered for cashier environments

Do not overuse gradients.

The UI should look similar in quality to modern commercial SaaS/POS systems.

Use a readable professional font.

Typography should prioritize:

- readability
- numbers
- price visibility
- barcode/SKU readability
- dense data tables

---

# 4. DESIGN PRINCIPLES

The interface must be:

- Professional
- Fast
- Responsive
- Minimal
- Dense but not cluttered
- Keyboard friendly
- Touch friendly
- Accessible
- Consistent
- Mobile responsive

The cashier should be able to operate the POS using:

- mouse
- keyboard
- touchscreen
- barcode scanner

Optimize the POS interface for 1366×768 and 1920×1080 desktop displays.

Also support:

- tablet
- mobile
- thermal receipt printers
- A4 printers

---

# 5. LOGIN SYSTEM

Create a professional login page.

Include:

- logo
- company name
- email/username
- password
- remember me
- forgot password
- login button
- validation
- account lockout/rate limiting
- session security
- optional 2FA architecture
- inactive user handling

After login redirect the user to the appropriate dashboard.

Users should not be able to access modules outside their permissions.

---

# 6. USER AND ROLE SYSTEM

Create a robust RBAC system.

Roles should include at minimum:

## Super Administrator

Full access to everything.

Permissions:

- system configuration
- users
- roles
- permissions
- stores
- warehouses
- registers
- products
- inventory
- purchasing
- sales
- reports
- customers
- suppliers
- accounting-related POS data
- audit logs
- integrations
- backup
- system maintenance

## Business Owner

Access:

- dashboards
- sales
- purchases
- inventory
- customers
- suppliers
- reports
- financial summaries
- store performance

May not modify system-critical settings.

## Manager

Access:

- sales
- inventory
- purchasing
- returns
- customers
- suppliers
- staff performance
- reports

## Cashier

Access:

- POS
- customer selection
- payments
- receipts
- own sales
- own cash register
- sales return if explicitly permitted

Cannot:

- change product cost
- change stock manually
- delete sales
- alter financial reports
- configure system

## Inventory Staff

Access:

- products
- inventory
- stock receiving
- stock adjustment
- stock transfers
- stock counts
- warehouses

## Purchasing Staff

Access:

- suppliers
- purchase orders
- receiving
- purchase returns

## Accountant / Finance

Access:

- financial reports
- sales reports
- purchase reports
- payment reports
- tax reports
- expenses
- reconciliation
- audit history

## Auditor

Read-only access to:

- sales
- purchases
- inventory
- payments
- user activities
- audit logs
- reports

No destructive operations.

---

# 7. GRANULAR PERMISSIONS

Do not rely only on roles.

Create granular permissions such as:

- dashboard.view
- products.view
- products.create
- products.update
- products.delete
- products.import
- products.export
- inventory.view
- inventory.adjust
- inventory.transfer
- inventory.count
- sales.view
- sales.create
- sales.update
- sales.cancel
- sales.return
- sales.refund
- sales.discount
- sales.void
- payments.create
- payments.refund
- purchases.view
- purchases.create
- purchases.approve
- purchases.receive
- customers.view
- customers.create
- customers.update
- customers.delete
- suppliers.view
- suppliers.create
- reports.view
- reports.export
- users.manage
- roles.manage
- settings.manage
- audit.view

Create a permission management interface.

---

# 8. MULTI-STORE ARCHITECTURE

Create:

- companies
- stores/outlets
- warehouses
- cash registers

A company can have multiple stores.

A store can have:

- multiple warehouses
- multiple registers
- multiple employees

Users may be assigned to:

- one company
- one or more stores
- optionally one primary store

Every business transaction should be scoped appropriately.

---

# 9. PRODUCT MANAGEMENT

Create a highly complete product system.

Product fields should include:

- id
- SKU
- barcode
- internal code
- product name
- slug
- description
- short description
- category
- brand
- unit
- purchase cost
- selling price
- wholesale price
- retail price
- minimum price
- tax class
- tax rate
- product type
- track inventory
- allow negative inventory
- low-stock threshold
- reorder level
- reorder quantity
- weight
- dimensions
- supplier
- default supplier
- manufacturer
- status
- featured
- image
- gallery
- created_by
- updated_by
- timestamps
- soft delete

Support product types:

- simple product
- variable product
- service
- bundle
- weighted product

---

# 10. PRODUCT VARIANTS

Support:

- size
- color
- material
- flavor
- model
- custom attributes

Example:

T-Shirt

- Small / Red
- Medium / Red
- Large / Red
- Small / Black
- Medium / Black
- Large / Black

Each variant can have:

- SKU
- barcode
- price
- cost
- stock
- weight
- image

---

# 11. CATEGORIES

Support hierarchical categories:

Example:

Food
→ Snacks
→ Drinks

Electronics
→ Mobile
→ Accessories

Use unlimited nesting where practical.

Include:

- name
- slug
- description
- parent_id
- image
- sort_order
- active

---

# 12. BRANDS

Fields:

- name
- slug
- logo
- description
- website
- status

---

# 13. UNITS

Support:

- pcs
- box
- carton
- kg
- gram
- liter
- ml
- meter
- pack
- bottle
- custom units

Create unit conversion support.

Example:

1 carton = 24 pcs

---

# 14. BARCODE SYSTEM

Support:

- EAN-13
- EAN-8
- UPC
- Code 128
- custom barcode
- internal barcode

Features:

- barcode lookup
- barcode validation
- barcode generation
- barcode printing
- product search by barcode

POS barcode scanner input should behave naturally as keyboard scanner input.

---

# 15. PRODUCT IMPORT/EXPORT

Support:

- CSV import
- Excel import if practical
- CSV export
- Excel export

Import must validate:

- duplicated SKU
- duplicated barcode
- invalid categories
- invalid prices
- missing required fields

Show an import preview before committing.

---

# 16. INVENTORY ENGINE

Create a proper stock ledger.

Every inventory movement must be traceable.

Movement types:

- purchase
- sale
- sales return
- purchase return
- adjustment in
- adjustment out
- transfer out
- transfer in
- stock opening
- stock count
- damaged
- expired
- lost
- manual correction

Never simply modify a product stock number without recording the movement.

Create an inventory ledger.

Each ledger entry should contain:

- product
- variant
- store
- warehouse
- movement type
- quantity
- unit cost
- reference type
- reference id
- before quantity
- after quantity
- user
- note
- timestamp

---

# 17. STOCK TRANSFER

Support:

Store A Warehouse → Store B Warehouse

Workflow:

1. Create transfer
2. Select source
3. Select destination
4. Select items
5. Submit
6. Approve
7. Ship
8. Receive
9. Complete

Support statuses:

- draft
- pending
- approved
- shipped
- partially_received
- received
- cancelled

---

# 18. STOCK COUNT / STOCKTAKE

Create professional stocktaking.

Workflow:

1. Create stock count
2. Select warehouse
3. Freeze/reference stock
4. Scan/count products
5. Enter physical quantity
6. Calculate variance
7. Review
8. Approve
9. Automatically generate adjustment ledger

Support:

- full stock count
- category count
- selected product count
- barcode scanning

---

# 19. SUPPLIER MANAGEMENT

Supplier fields:

- company name
- contact person
- phone
- email
- address
- city
- province
- postal code
- tax number
- payment terms
- credit limit
- notes
- status

Include supplier purchase history.

---

# 20. PURCHASING

Create purchasing module.

Workflow:

Draft Purchase Order
→ Approval
→ Sent to Supplier
→ Receiving
→ Partial Receiving
→ Complete
→ Purchase Invoice
→ Payment

Purchase order fields:

- PO number
- supplier
- store
- warehouse
- order date
- expected date
- items
- quantities
- cost
- discount
- tax
- shipping
- subtotal
- grand total
- notes
- status

---

# 21. PURCHASE RECEIVING

Support partial delivery.

Example:

Ordered: 100 pcs  
Received: 70 pcs

Remaining:

30 pcs

Allow another receiving later.

Every receiving transaction must update the inventory ledger.

---

# 22. PURCHASE RETURN

Allow users with permission to return purchased products.

Capture:

- supplier
- product
- quantity
- reason
- cost
- reference purchase
- warehouse
- user
- status

Stock must be deducted correctly.

---

# 23. CUSTOMER MANAGEMENT

Customer profile:

- customer code
- name
- phone
- email
- gender if appropriate
- date of birth if required
- address
- city
- province
- postal code
- tax ID
- customer group
- credit limit
- outstanding balance
- points
- notes
- status

Support:

- walk-in customer
- registered customer
- wholesale customer
- VIP customer

---

# 24. CUSTOMER GROUPS

Example:

- Walk-in
- Retail
- Wholesale
- VIP
- Distributor

Each group may have separate:

- price list
- discount
- payment terms

---

# 25. COMPLETE POS SCREEN

The POS screen is the most important interface.

Create a fast interface with:

LEFT SIDE:

- product search
- barcode input
- category filter
- brand filter
- favorites
- product grid
- product image
- SKU
- price
- stock status

RIGHT SIDE:

- cart
- quantity controls
- discount
- tax
- subtotal
- rounding
- grand total
- customer
- payment button

Top:

- store
- warehouse
- register
- cashier
- shift status

Bottom:

- hold
- retrieve
- discount
- customer
- notes
- void item
- clear cart
- payment
- receipt

---

# 26. POS KEYBOARD SHORTCUTS

Provide keyboard shortcuts.

Example:

F1 = Search  
F2 = Customer  
F3 = Discount  
F4 = Hold Sale  
F5 = Retrieve Sale  
F6 = Cash Payment  
F7 = Card Payment  
F8 = Other Payment  
F9 = Complete Sale  
ESC = Close Modal  
DEL = Remove Item

Allow configuration later.

Show shortcuts inside help modal.

---

# 27. CART ENGINE

Cart must support:

- add item
- remove item
- change quantity
- price override if permitted
- item discount
- global discount
- tax
- notes
- customer
- barcode scan
- manual product search
- stock validation

Prevent selling out-of-stock items unless permission/settings allow negative stock.

---

# 28. SALES WORKFLOW

Complete workflow:

Open Register
→ Start Shift
→ Add Products
→ Select Customer
→ Apply Discount
→ Calculate Tax
→ Select Payment
→ Confirm Payment
→ Generate Invoice
→ Update Inventory
→ Generate Receipt
→ Record Audit Log

Everything must execute in a database transaction.

If one critical operation fails, do not leave partial sale records.

---

# 29. SALE DOCUMENTS

Create:

- quotation
- sales order
- invoice
- receipt
- sales return
- refund

Each document requires unique numbering.

Example:

INV-2026-000001

Configurable numbering prefixes per store.

---

# 30. PAYMENT SYSTEM

Support multiple payment methods:

- Cash
- Bank Transfer
- Debit Card
- Credit Card
- QRIS
- E-Wallet
- Other

Allow multiple payments for one sale.

Example:

Total: Rp100,000

Cash: Rp50,000  
QRIS: Rp50,000

Support split payments.

---

# 31. CASH PAYMENT

Cash payment must calculate:

- amount due
- amount tendered
- change

Show large readable numbers.

Example:

TOTAL

Rp125.000

CASH RECEIVED

Rp150.000

CHANGE

Rp25.000

---

# 32. PAYMENT VALIDATION

Do not allow completion if:

amount paid < amount due

unless:

- credit sale
- partial payment
- account receivable is intentionally enabled

---

# 33. RETURNS AND REFUNDS

Support:

- full return
- partial return
- item-level return
- exchange where practical
- cash refund
- original payment refund
- store credit

Return must reference original invoice.

Never allow uncontrolled return transactions without traceability.

---

# 34. SALE VOID / CANCEL

Require permission.

For sensitive operations require:

- reason
- confirmation
- audit record

Optionally require manager approval.

Never physically delete completed sales.

Use statuses such as:

- completed
- cancelled
- voided
- refunded
- partially_refunded

---

# 35. CASH REGISTER MANAGEMENT

Create:

- registers
- register sessions
- opening balance
- closing balance
- cash in
- cash out
- paid out
- cash adjustment

Workflow:

Open Register
→ Cashier works
→ Cash In/Out
→ Close Register
→ Count Cash
→ Compare Expected vs Actual
→ Record Difference
→ Close Session

---

# 36. CASH SHIFT

Each shift must have:

- opening amount
- expected cash
- actual cash
- variance
- cashier
- register
- opening timestamp
- closing timestamp

Expected:

opening cash
+ cash sales
+ cash in
- cash refunds
- cash out

Compare against actual physical cash.

---

# 37. EXPENSE MANAGEMENT

Create expense module.

Examples:

- electricity
- transportation
- packaging
- maintenance
- office supplies
- internet
- miscellaneous

Fields:

- expense number
- category
- amount
- payment method
- store
- register
- date
- description
- attachment
- created by
- approval status

---

# 38. DISCOUNT ENGINE

Support:

- percentage discount
- fixed amount discount
- item discount
- cart discount
- customer group discount
- promotional discount
- scheduled discount

Rules may include:

- minimum purchase
- minimum quantity
- date range
- customer group
- product/category
- outlet

---

# 39. PROMOTION ENGINE

Design extensible promotion support.

Examples:

Buy 1 Get 1

Buy 2 Get 1

10% off

Rp20.000 discount above Rp200.000

Category promotion

Customer-specific price

Happy hour

Scheduled promotion

Do not hard-code promotions into POS logic.

Use a reusable pricing/promotion service.

---

# 40. PRICE LISTS

Support:

- retail price
- wholesale price
- VIP price
- distributor price
- store-specific price

Future-proof the schema for effective dates.

---

# 41. TAX ENGINE

Support:

- tax-inclusive pricing
- tax-exclusive pricing
- tax percentage
- tax exemptions
- configurable tax classes

Tax calculation must be centralized.

Do not duplicate tax formulas in multiple controllers.

---

# 42. ROUNDING

Support configurable rounding:

- none
- nearest 10
- nearest 50
- nearest 100

Show:

subtotal
discount
tax
rounding
grand total

---

# 43. LOYALTY PROGRAM

Optional but recommended.

Support:

- points earning
- points redemption
- customer levels
- point expiration
- manual adjustment
- loyalty history

Example:

Rp10.000 = 1 point

Make formula configurable.

---

# 44. DASHBOARD

Create a beautiful executive dashboard.

Cards:

- Today's Sales
- Today's Transactions
- Today's Profit
- Average Basket
- Low Stock
- Pending Purchases
- Returns
- Cash Position

Charts:

- sales by day
- sales by hour
- sales by category
- sales by payment method
- top products
- top customers
- store performance

Use proper date filters.

---

# 45. REPORTING SYSTEM

Create a comprehensive reporting module.

Reports:

## Sales

- daily sales
- weekly sales
- monthly sales
- sales by cashier
- sales by store
- sales by product
- sales by category
- sales by brand
- sales by customer
- sales by payment
- sales by hour

## Inventory

- current stock
- stock valuation
- stock movement
- stock adjustment
- stock transfer
- stock count
- low stock
- dead stock
- fast-moving products

## Purchasing

- purchase orders
- purchases by supplier
- purchase by product
- purchase receiving
- purchase return

## Financial

- revenue
- discounts
- tax
- refunds
- expenses
- cash movement
- register variance
- gross profit

Every report must support:

- date range
- outlet
- warehouse
- cashier
- category
- product
- customer
- supplier

Where logical.

---

# 46. PROFIT CALCULATION

Support gross profit:

Revenue - COGS = Gross Profit

Use a consistent inventory-costing strategy.

Prefer a design capable of supporting:

- weighted average
- FIFO architecture

Do not calculate profit from current product cost alone.

Sales must preserve the historical cost used at the time of transaction.

---

# 47. AUDIT LOG

Create a serious audit system.

Record:

- login
- logout
- user creation
- role modification
- product modification
- price modification
- stock adjustment
- sale creation
- sale cancellation
- refund
- purchase creation
- payment
- cash adjustment
- settings changes

Capture:

- user
- action
- module
- record type
- record id
- old values
- new values
- IP address
- user agent
- timestamp

Audit logs must be protected from ordinary users.

---

# 48. NOTIFICATION SYSTEM

Create notifications for:

- low stock
- purchase pending
- stock transfer waiting
- approval required
- register variance
- failed job
- important system events

Use database notifications initially.

Structure it so email/WhatsApp/etc. can be added later.

---

# 49. SETTINGS

Create system configuration pages.

Settings:

## Business

- company name
- address
- phone
- email
- logo
- tax ID

## Store

- store name
- address
- timezone
- currency
- tax configuration

## POS

- default customer
- allow negative stock
- receipt format
- rounding
- default payment
- barcode behavior
- automatic receipt printing

## Inventory

- costing method
- low stock behavior
- transfer rules

## Numbering

- invoice prefix
- purchase prefix
- return prefix
- customer code prefix
- supplier code prefix

---

# 50. RECEIPT SYSTEM

Create a professional thermal receipt.

Support:

58mm and 80mm.

Receipt contains:

- company logo
- company name
- store
- address
- invoice number
- cashier
- date/time
- customer
- items
- qty
- unit price
- discount
- subtotal
- tax
- rounding
- total
- payment
- change
- thank-you message
- optional QR/barcode

Create print-friendly CSS.

---

# 51. A4 INVOICE

Create professional A4 invoice.

Include:

- company branding
- customer details
- invoice information
- product table
- discount
- tax
- subtotal
- grand total
- payment information
- terms
- notes

Allow PDF generation.

---

# 52. DATABASE DESIGN

Create a comprehensive normalized schema.

At minimum design tables for:

- companies
- stores
- warehouses
- registers
- register_sessions
- users
- roles
- permissions
- role_permissions
- user_roles
- user_stores
- customers
- customer_groups
- customer_addresses
- loyalty_accounts
- loyalty_transactions
- suppliers
- supplier_contacts
- categories
- brands
- units
- unit_conversions
- products
- product_variants
- product_barcodes
- product_images
- product_prices
- price_lists
- price_list_items
- taxes
- tax_classes
- promotions
- promotion_rules
- promotion_items
- inventory_stocks
- inventory_ledgers
- inventory_adjustments
- inventory_adjustment_items
- stock_counts
- stock_count_items
- stock_transfers
- stock_transfer_items
- purchase_orders
- purchase_order_items
- purchase_receipts
- purchase_receipt_items
- purchase_returns
- purchase_return_items
- sales
- sale_items
- sale_item_taxes
- sale_discounts
- payments
- payment_methods
- sales_returns
- sales_return_items
- refunds
- cash_movements
- expenses
- expense_categories
- quotations
- quotation_items
- sales_orders
- sales_order_items
- audit_logs
- notifications
- system_settings
- document_sequences
- attachments

Add additional tables whenever business logic requires them.

Do not force everything into a few oversized tables.

---

# 53. IMPORTANT DATABASE RULES

Every financial document requires:

- unique document number
- status
- created_by
- approved_by where applicable
- timestamps

Every monetary value uses:

DECIMAL(19,4)

or an appropriate fixed-precision decimal.

Use foreign key constraints.

Use indexes on:

- SKU
- barcode
- invoice number
- PO number
- customer
- supplier
- store
- warehouse
- product
- created_at
- transaction date

Optimize search indexes appropriately.

---

# 54. SOFT DELETE POLICY

Use soft deletes for master data when historical transactions depend on them:

- products
- customers
- suppliers
- categories
- users where appropriate

Never delete financial transaction history.

---

# 55. TRANSACTION INTEGRITY

Critical workflows MUST use database transactions.

Especially:

- completing sale
- refund
- return
- purchase receiving
- stock adjustment
- transfer receiving
- register closing

Use row locking where required to prevent stock race conditions.

Example concept:

select stock row for update
→ validate quantity
→ update stock
→ insert inventory ledger
→ insert sale item
→ commit

Prevent double-selling due to concurrent transactions.

---

# 56. SERVICE ARCHITECTURE

Do not put all business logic inside controllers.

Use service classes such as:

- SaleService
- PaymentService
- InventoryService
- StockTransferService
- PurchaseService
- PromotionService
- PricingService
- TaxService
- RegisterService
- RefundService
- LoyaltyService
- ReportingService
- DocumentNumberService
- AuditService

Use repositories only where they provide real value.

Keep controllers thin.

---

# 57. EVENTS

Create useful domain events.

Examples:

- SaleCompleted
- SaleRefunded
- InventoryAdjusted
- PurchaseReceived
- StockTransferReceived
- RegisterOpened
- RegisterClosed
- LowStockDetected

Use listeners/jobs where appropriate.

---

# 58. QUEUES

Use queues for expensive/background operations:

- report generation
- PDF generation
- imports
- exports
- notifications
- bulk operations

Do not unnecessarily queue operations that the cashier must wait for.

---

# 59. SEARCH

POS product search must be extremely fast.

Search by:

- barcode
- SKU
- product name
- variant
- brand
- category

Prioritize barcode exact matches.

Show results with:

- image
- name
- SKU
- barcode
- price
- stock

---

# 60. SECURITY

Implement:

- CSRF protection
- XSS protection
- SQL injection prevention
- mass-assignment protection
- authentication throttling
- password hashing
- authorization policies
- session security
- secure cookies
- input validation
- output escaping
- file upload validation
- MIME validation
- maximum upload size
- audit logs

Never trust frontend permission checks alone.

Every sensitive action must be authorized server-side.

---

# 61. FILE UPLOADS

Product images:

- validate MIME type
- validate size
- sanitize filenames
- resize images
- create thumbnails
- avoid executable uploads

Store using Laravel filesystem.

---

# 62. API ARCHITECTURE

Create a clean internal API structure where useful.

Version API:

/api/v1/

Potential endpoints:

- authentication
- products
- categories
- customers
- suppliers
- inventory
- sales
- purchases
- reports

Use:

- API Resources
- Form Requests
- authentication
- authorization
- rate limiting

Do not expose sensitive endpoints without authorization.

---

# 63. RESPONSIVE LAYOUT

Desktop:

Sidebar + top navigation + content.

POS:

Full-screen optimized cashier workspace.

Tablet:

Condensed sidebar + touch-friendly controls.

Mobile:

Bottom navigation / drawer style navigation where useful.

---

# 64. MAIN NAVIGATION

Suggested navigation:

Dashboard

Sales
- POS
- Sales History
- Quotations
- Sales Orders
- Returns
- Refunds

Purchasing
- Suppliers
- Purchase Orders
- Receiving
- Purchase Returns

Inventory
- Products
- Categories
- Brands
- Stock
- Adjustments
- Stock Count
- Transfers
- Warehouses

Customers
- Customers
- Groups
- Loyalty

Finance
- Payments
- Expenses
- Cash Register

Reports
- Sales
- Products
- Inventory
- Purchasing
- Customers
- Cash
- Profit

Administration
- Users
- Roles
- Stores
- Registers
- Settings
- Audit Logs

---

# 65. DASHBOARD UX

Create useful empty states.

Do not display fake numbers in production.

Seed demo data only in development.

Every chart must have proper empty states.

---

# 66. DATA VALIDATION

Use Laravel Form Requests.

Create separate validation for:

- products
- variants
- customers
- suppliers
- purchases
- sales
- payments
- returns
- inventory adjustments
- stock transfers

Return meaningful error messages.

---

# 67. ERROR HANDLING

Create user-friendly errors.

Never expose:

- SQL errors
- stack traces
- credentials
- environment variables

Production APP_DEBUG must be false.

---

# 68. PERFORMANCE

Optimize for:

- product search
- POS cart
- inventory query
- dashboard
- reports

Use:

- eager loading
- indexes
- pagination
- caching
- query optimization
- database transactions

Avoid N+1 queries.

---

# 69. TESTING

Create a full test suite.

Unit tests:

- pricing
- discounts
- tax
- promotion
- inventory
- profit
- rounding

Feature tests:

- login
- permissions
- create product
- sale
- payment
- refund
- purchase
- receiving
- transfer
- register closing

Important test:

When a sale is completed:

1. Sale exists
2. Sale items exist
3. Payment exists
4. Stock decreases
5. Inventory ledger exists
6. Audit log exists
7. Correct totals are stored

Test transaction rollback.

Test concurrent stock conditions where feasible.

---

# 70. DEVELOPMENT SEED DATA

Create realistic seed data.

Create:

- demo company
- 2 stores
- 2 warehouses
- 3 registers
- categories
- brands
- 50+ realistic products
- variants
- suppliers
- customers
- payment methods
- roles
- permissions
- users
- realistic transactions

Do not use lorem ipsum.

Use realistic Indonesian business data.

Use Indonesian Rupiah:

Rp

Currency:

IDR

Timezone:

Asia/Jakarta

But the **application interface itself should use English text** unless a localization system is implemented later.

---

# 71. LANGUAGE

All UI labels should initially be English.

Examples:

- Dashboard
- Point of Sale
- Products
- Inventory
- Purchase Orders
- Customers
- Suppliers
- Reports
- Settings

Use clean, professional English.

Prepare the application for future localization.

---

# 72. SEED ADMIN ACCOUNT

Create a development seed account.

Do not hard-code a production password into the codebase.

Use environment variables or a documented installation command.

Example:

php artisan db:seed --class=AdminSeeder

---

# 73. INSTALLATION SCRIPT

Provide setup documentation:

composer install
npm install
npm run build

Create:

.env

Configure:

DB_DATABASE
DB_USERNAME
DB_PASSWORD
APP_URL

Run:

php artisan key:generate
php artisan migrate --seed
php artisan storage:link

Optimize:

php artisan config:cache
php artisan route:cache
php artisan view:cache

---

# 74. APACHE DEPLOYMENT

The target server uses Apache2.

The Laravel document root MUST point to:

/path/to/project/public

Never point Apache directly to the Laravel project root.

Create proper Apache VirtualHost configuration for:

pos.aksisoft.web.id

Example concept:

<VirtualHost *:80>
    ServerName pos.aksisoft.web.id
    DocumentRoot /var/www/pos/public

    <Directory /var/www/pos/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/pos-error.log
    CustomLog ${APACHE_LOG_DIR}/pos-access.log combined
</VirtualHost>

Use the actual deployment directory discovered on the VPS instead of blindly assuming `/var/www/pos`.

---

# 75. APACHE AUTOMATION

You are explicitly authorized to inspect the VPS and modify Apache configuration.

Before changing anything:

1. Inspect current Apache configuration.
2. Inspect enabled sites.
3. Inspect available sites.
4. Determine existing PHP version.
5. Determine PHP-FPM socket if applicable.
6. Determine Laravel project location.
7. Back up the Apache configuration before modification.
8. Create or modify the required VirtualHost.
9. Enable the site.
10. Run apache config validation.
11. Reload Apache only after configuration passes validation.

Use commands such as:

apache2ctl configtest

and inspect:

/etc/apache2/sites-available/
/etc/apache2/sites-enabled/

Do not overwrite unrelated VirtualHosts.

Do not break existing websites.

---

# 76. CLOUDFLARE CONNECTOR

Use the available Cloudflare connector/tool to configure DNS.

Target:

pos.aksisoft.web.id

First determine:

- Cloudflare zone
- zone ID
- existing DNS records
- existing A/CNAME record for pos

Then create or update:

Type: A

Name:

pos

Content:

<VPS PUBLIC IPv4>

TTL:

Automatic

Proxy:

Use the existing desired Cloudflare policy, preferably proxied if appropriate for the deployment.

Cloudflare's current API supports creating DNS records through the DNS records endpoint and requires appropriate DNS write permission; A records require a valid IPv4 content value.

Do NOT create duplicate conflicting records.

If an existing `pos` record exists:

- inspect it
- compare it with the VPS IP
- update it only when appropriate

Do not modify unrelated DNS records.

---

# 77. CLOUDFLARE SAFETY

Never print or commit:

- API tokens
- API secrets
- SSH credentials
- database passwords
- .env contents

Never put secrets into Git.

Use environment variables or connected credentials.

Never expose credentials inside logs.

---

# 78. HTTPS

After DNS is configured:

verify DNS resolution.

Then configure HTTPS.

If the server already uses a Cloudflare-compatible HTTPS configuration:

- inspect current configuration
- do not break other domains

Enable HTTPS for:

https://pos.aksisoft.web.id

Verify:

- HTTP redirects to HTTPS where appropriate
- certificate works
- Laravel APP_URL is correct
- secure cookies work
- CSRF works
- assets load

---

# 79. VPS DISCOVERY

Before deployment, inspect:

- Linux distribution
- Apache version
- PHP version
- PHP modules
- Composer version
- Node/npm
- MySQL/MariaDB
- available disk space
- RAM
- CPU
- current Apache sites
- current project directories

Adapt installation to the real VPS environment.

Do not blindly assume Ubuntu/Debian paths without checking.

---

# 80. GIT WORKFLOW

Use Git throughout development.

Before changing anything:

git status

Create a clean feature branch if appropriate.

Commit logically.

Examples:

feat: initialize Laravel POS architecture

feat: add product and inventory modules

feat: add POS transaction engine

feat: add purchasing workflow

feat: add reports and dashboard

chore: configure Apache deployment

chore: configure Cloudflare DNS

fix: resolve deployment issues

Do not commit:

.env
credentials
private keys
passwords
large generated artifacts unless explicitly intended

---

# 81. GITHUB ACTIONS

Create GitHub Actions CI/CD.

Pipeline should:

1. Checkout
2. Install PHP
3. Install Composer
4. Install Node
5. Install dependencies
6. Run Pint
7. Run static analysis if configured
8. Run tests
9. Build frontend assets
10. Package/deploy when appropriate

Use secrets for:

- SSH
- deployment key
- server address
- username
- application secrets

Never hard-code secrets.

---

# 82. CI TEST GATES

Deployment MUST NOT proceed if:

- composer install fails
- npm build fails
- tests fail
- migrations fail in validation environment
- lint fails where configured

---

# 83. DEPLOYMENT STRATEGY

Prefer atomic or low-downtime deployment.

Deployment concept:

GitHub
→ CI
→ Build/Test
→ Deploy
→ Run migrations
→ Cache config/routes/views
→ Restart workers if necessary
→ Health check

Do not blindly run destructive database operations.

Never use:

migrate:fresh

on production.

---

# 84. HEALTH CHECK

Create:

/health

It should verify application availability.

Optionally verify:

- database
- cache
- filesystem

Return a simple JSON response.

Example:

{
  "status": "ok"
}

Do not expose sensitive infrastructure information.

---

# 85. BACKUP

Provide backup strategy.

Database:

- automated daily backup
- retention policy
- documented restore command

Storage:

- product images
- attachments

Do not claim backups exist unless actually configured.

---

# 86. OBSERVABILITY

Log meaningful events.

Separate:

- application logs
- authentication/security events
- queue failures
- deployment issues

Configure log rotation.

Do not create uncontrolled giant log files.

---

# 87. INSTALLATION WIZARD

If practical, create an installer.

Installation flow:

1. Environment check
2. Database configuration
3. Migration
4. Admin account
5. Company setup
6. Store setup
7. Register setup
8. Payment method setup
9. Finish

Disable or protect installer after setup.

---

# 88. UI DETAIL

Use professional components:

- cards
- tables
- tabs
- drawers
- modals
- dropdowns
- badges
- alerts
- toast notifications
- confirmation dialogs
- skeleton loaders
- empty states
- pagination
- filters

Avoid excessive rounded cards everywhere.

Use spacing consistently.

---

# 89. POS COLORS

Suggested semantic colors:

Primary:

Deep navy / indigo

Success:

Green

Warning:

Amber

Danger:

Red

Info:

Blue

Neutral:

Gray

Ensure sufficient contrast.

Do not rely on color alone to communicate status.

---

# 90. PRODUCT CARD

Product card:

- image
- name
- SKU
- price
- stock badge
- quick add button

For low-stock:

show a visible Low Stock state.

For unavailable:

show Out of Stock.

---

# 91. MOBILE POS

On small screens:

- cart can become a bottom sheet
- product grid remains touch friendly
- payment uses large buttons
- barcode scanner input remains functional
- no horizontal scrolling for normal POS operations

---

# 92. ACCESSIBILITY

Implement:

- keyboard navigation
- focus states
- semantic HTML
- readable labels
- contrast
- accessible buttons
- ARIA only where needed

---

# 93. AUDITABILITY REQUIREMENT

A manager must be able to answer:

Who sold this?

When?

At which store?

Which register?

Which cashier?

Which customer?

What products?

What prices?

What discounts?

What tax?

What payment?

What stock change occurred?

Was it refunded?

Who approved the refund?

Therefore, preserve all relevant historical data.

---

# 94. FINANCIAL IMMUTABILITY

Completed sales should not be edited directly.

Use controlled correction workflows:

- void
- refund
- return
- adjustment
- manager approval

Historical financial records must remain auditable.

---

# 95. UX FOR DANGEROUS ACTIONS

For actions such as:

- delete
- void sale
- refund
- stock adjustment
- price override
- close register

require confirmation.

For sensitive operations, require reason and/or elevated permission.

---

# 96. CODE QUALITY

Follow:

- PSR standards
- Laravel conventions
- clean naming
- SRP
- SOLID where practical
- DRY without overengineering

Avoid:

- giant controllers
- duplicated business logic
- raw SQL everywhere
- hidden magic rules
- hard-coded business calculations

---

# 97. DOCUMENTATION

Create:

README.md

Include:

- requirements
- installation
- environment configuration
- database setup
- seeding
- local development
- production deployment
- Apache setup
- Cloudflare DNS
- cron
- queue workers
- backups
- troubleshooting

Also create:

docs/architecture.md

docs/database.md

docs/deployment.md

docs/pos-workflow.md

docs/security.md

docs/permissions.md

---

# 98. CRON AND QUEUE

Configure Laravel scheduler.

Document:

* * * * * php /path/to/artisan schedule:run

Configure queue worker where required.

Document supervisor/systemd configuration if appropriate.

---

# 99. DATABASE DOCUMENTATION

Generate a database documentation section listing:

- every table
- every important field
- foreign keys
- indexes
- relationships
- business purpose

Also generate an ERD if practical.

---

# 100. SEED REALISTIC BUSINESS FLOW

Create a development dataset that demonstrates:

Company

→ Store

→ Warehouse

→ Register

→ Employees

→ Products

→ Supplier

→ Purchase

→ Receiving

→ Stock

→ Customer

→ Sale

→ Payment

→ Receipt

→ Inventory Ledger

→ Report

The demo environment must look realistic immediately after seeding.

---

# 101. COMPLETE END-TO-END SCENARIO

After implementation, verify this exact scenario:

1. Login as administrator.
2. Create store.
3. Create warehouse.
4. Create register.
5. Create category.
6. Create brand.
7. Create unit.
8. Create product.
9. Add barcode.
10. Create supplier.
11. Create purchase order.
12. Receive products.
13. Confirm stock increased.
14. Open cashier register.
15. Login as cashier.
16. Scan product.
17. Add customer.
18. Add second product.
19. Apply discount.
20. Calculate tax.
21. Pay with cash.
22. Confirm change.
23. Complete sale.
24. Print receipt.
25. Confirm inventory decreased.
26. Confirm inventory ledger exists.
27. Confirm payment exists.
28. Confirm audit record exists.
29. Return one item.
30. Confirm refund.
31. Confirm stock adjustment.
32. Close register.
33. Compare expected vs actual cash.
34. View sales report.
35. View stock report.
36. View profit report.

Everything must work without manually fixing database rows.

---

# 102. NO FAKE FEATURES

Do not create buttons that do nothing.

Do not create placeholder pages saying:

"Coming soon"

for core POS functionality.

If a feature is visible, it should actually work.

If a feature cannot realistically be implemented in the current scope, do not pretend it is implemented.

---

# 103. IMPLEMENTATION ORDER

Use this order:

Phase 1:
Architecture and environment discovery

Phase 2:
Laravel project initialization

Phase 3:
Database schema and migrations

Phase 4:
Authentication and RBAC

Phase 5:
Master data

Phase 6:
Inventory engine

Phase 7:
Purchasing

Phase 8:
POS sales engine

Phase 9:
Payments and registers

Phase 10:
Returns/refunds

Phase 11:
Reports/dashboard

Phase 12:
Audit/security

Phase 13:
UI polishing

Phase 14:
Testing

Phase 15:
CI/CD

Phase 16:
Cloudflare DNS

Phase 17:
Apache VirtualHost

Phase 18:
HTTPS verification

Phase 19:
Production deployment

Phase 20:
Final end-to-end validation

---

# 104. IMPORTANT AGENT BEHAVIOR

You are not merely writing code snippets.

You must actually work through the project.

When operating inside the repository:

1. Inspect the repository.
2. Inspect existing files before modifying them.
3. Preserve existing useful work.
4. Do not overwrite unrelated projects.
5. Detect existing Laravel installation.
6. Detect current database configuration.
7. Detect server environment.
8. Build incrementally.
9. Run tests.
10. Fix errors.
11. Re-run tests.
12. Verify the browser/application.
13. Commit changes.
14. Push changes when authorized.

---

# 105. CLOUDFLARE + VPS DEPLOYMENT TASK

At deployment time:

### Cloudflare

Using the available Cloudflare connector:

- inspect zone
- inspect existing DNS
- resolve VPS public IPv4
- create/update `pos` A record
- verify resulting DNS configuration

### VPS

Inspect:

/etc/apache2/sites-available/

/etc/apache2/sites-enabled/

Then:

- create `/etc/apache2/sites-available/pos.aksisoft.web.id.conf`
- configure Laravel public directory
- enable required Apache modules
- enable the site
- validate Apache config
- reload Apache
- verify application

Do not disturb unrelated sites.

---

# 106. FINAL PRODUCTION VALIDATION

Before declaring success, check:

DNS resolves:

pos.aksisoft.web.id

Application:

https://pos.aksisoft.web.id

Check:

- login works
- dashboard works
- product creation works
- barcode search works
- POS works
- payment works
- receipt works
- stock decreases
- purchase receiving works
- stock increases
- return works
- refund works
- register works
- reports work
- permissions work
- audit logs work

Also verify:

- Apache has no syntax errors
- Laravel logs contain no new critical errors
- queue is operational if enabled
- scheduler is configured
- assets load correctly
- HTTPS works
- no mixed-content errors
- no exposed `.env`
- no debug mode in production

---

# 107. FINAL OUTPUT REQUIRED FROM THE AI AGENT

At the end, provide a concise implementation report containing:

## Application

- Laravel version
- PHP version
- MySQL version
- frontend stack

## Modules Implemented

List all completed modules.

## Database

- migration count
- major tables
- seed data

## Security

- authentication
- RBAC
- audit
- validation

## Deployment

- server
- Apache VirtualHost
- domain
- HTTPS status

## Cloudflare

- A record status
- proxy status
- DNS verification

## Git

- branch
- latest commit
- push status

## Testing

- tests executed
- passed
- failed
- remaining issues

## Production URL

https://pos.aksisoft.web.id

Do not say "completed" if important functionality is still broken.

---

# 108. MOST IMPORTANT PRINCIPLE

Build this as a **real POS product**, not as a generic Laravel CRUD dashboard.

The cashier workflow must be extremely fast.

Inventory must be financially and operationally reliable.

Every transaction must be traceable.

Permissions must be enforced server-side.

Database transactions must protect stock and payment integrity.

The UI must look commercially polished.

The database must support future expansion.

The architecture must remain maintainable.

The deployment must be reproducible.

The final result should feel like a professional SaaS POS application that could realistically be handed to a store owner.

Start by inspecting the repository and server environment, then implement the system incrementally and validate every major workflow before moving to the next phase.