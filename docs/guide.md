# Dashboard usage guide

## Purpose

Stockroom helps a small team manage products, customers, orders and inventory. The demo shows backend behavior through a working Filament interface.

## Dashboard

Confirmed sales is the sum of confirmed order totals. It is gross order value, not money collected. Orders includes confirmed and cancelled orders. Low stock counts active products whose available quantity is at or below the reorder level. The chart shows confirmed sales for the last fourteen days.

## Products

Search by product name. Use the filter button and select Low stock only to find products needing attention.

Administrators can create or edit a product. Enter prices as integer cents: 2500 means USD 25.00. New products begin with zero stock. Use Adjust stock to receive inventory. A positive quantity adds stock and a negative quantity removes it. A reason of at least five characters is required. An adjustment that would make stock negative is rejected.

Deactivate a product to stop new orders. Existing order lines keep their recorded name and price. Product deletion is intentionally unavailable.

## Customers

Administrators can create and edit customers. Email addresses must be unique. Staff can view the customer list and select customers when placing orders. Demo addresses use reserved test domains.

## Orders

1. Select Place order.
2. Choose an existing customer.
3. Select an active product and enter the quantity.
4. Add another product if needed. Each product can appear only once in an order.
5. Confirm the order.

The server rechecks stock and prices at submission time. A stale quantity shown in the form does not reserve inventory. If any line fails, no order or stock change is saved.

Use Details to inspect saved line items. A confirmed order may be cancelled. Administrators may cancel any order; staff may cancel only orders they placed. Cancellation is final in this version and restores each line quantity once.

## Reports

Only administrators can generate reports. Select inclusive start and end dates in UTC. The queue processes the request in the background.

Queued means the worker has not started. Processing means the worker is creating the file. Ready exposes Download CSV. Failed exposes Retry. The job has three automatic attempts with increasing delays before it is marked failed.

A report includes orders that are confirmed when the worker generates it. It is a saved export, not a continuously updated view. Regenerate the report if an order changes afterward. Each administrator sees and downloads only their own reports.

If a report remains queued, start the queue worker described in the README. Private files are stored under storage/app/private/reports. Do not expose that directory through the web server.

## Activity history

Administrators can inspect confirmation, cancellation, stock adjustment and report request events. Each event records an actor, subject, timestamp and supporting details. This is an operational activity history, not a tamper proof audit system. Product and customer field edits are not recorded in this release.

## Suggested demonstration

1. Review the low stock count.
2. Add inventory with an adjustment reason.
3. Place an order for two products.
4. Check the stock reduction.
5. Cancel the order and check stock restoration.
6. Generate a report and download its CSV.
7. Inspect Activity history.
8. Repeat as staff to see the permission boundaries.
