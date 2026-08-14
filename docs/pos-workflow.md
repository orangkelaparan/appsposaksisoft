# POS Workflows

## Cashier sale

1. Open a register session with its physical opening balance.
2. Enter **Point of Sale**; scan a barcode or search by SKU/name.
3. Add products, adjust quantities, optionally choose a registered customer, and apply an allowed cart discount.
4. Select Cash, QRIS, or Card. For cash, enter the tendered amount and confirm the calculated change.
5. Complete the sale. The system atomically creates the sale, immutable sale lines, payment, stock ledger movement, and audit entry.
6. Print or re-open the thermal receipt from the returned invoice link.

## Purchase and receiving

1. Create an approved purchase order from a supplier, warehouse, product, quantity, and unit cost.
2. The order begins in the approved state and retains its ordered quantity.
3. Use **Receive outstanding** when stock physically arrives. The receiving service blocks over-receipt, creates a receipt document, increases stock, writes a purchase inventory ledger entry, and marks the PO partially received or completed.

## Return

A controlled return must reference a completed sale line. The return service validates quantity, creates a return document, restores the warehouse stock through a `sales_return` ledger record, updates sale state to partially refunded, and writes audit history. A return is therefore traceable to its original invoice and historical cost.

## Register close

1. After business hours, count physical cash.
2. Open the **Cash Register** page and submit the actual cash figure for the open session.
3. The system calculates expected cash from opening balance plus cash payments and stores the variance. The session changes to closed and cannot accept further transactions.

## Keyboard shortcuts

| Shortcut | Action |
| --- | --- |
| `F1` | Focus product / barcode search. |
| `F6` | Cash payment selection. |
| `F7` | QRIS payment selection. |
| `F8` | Card payment selection. |
| `F9` | Complete current sale. |
| `Esc` | Clear current search term. |

The POS is designed for mouse, keyboard, touch input, and keyboard-emulating barcode scanners.
