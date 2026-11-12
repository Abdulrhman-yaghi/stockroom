# Architecture and tradeoffs

## Stack

Laravel 13, Filament 5, Livewire 4, PHP 8.4, SQLite and a database backed queue. Exact dependency versions are recorded in composer.lock and package-lock.json.

## Data model

Users have an administrator or staff role. Products contain a unique SKU, integer price, stock quantity, reorder level and active state. Customers contain a unique email and company name.

An order belongs to a customer and a user. Its items reference products and snapshot their names and unit prices. Orders retain their original totals after cancellation. Reports belong to the user who requested them. Activities reference the actor and describe an operational event.

## Transactional ordering

OrderService validates the complete request and checks the create policy. Products are processed in a stable identifier order. Each stock deduction uses a conditional update requiring sufficient stock and an active product. Order creation, line creation, deductions and the activity event share a transaction.

SQLite uses an immediate transaction and a five second busy timeout. This acquires the writer lock before the read and write sequence. The service also requests row locks for engines that support them. A two process SQLite test verifies that two buyers cannot both purchase one remaining unit. Equivalent behavior on other database engines still needs an integration run before deployment there.

## Cancellation

A conditional update changes only confirmed orders to cancelled. The code restores inventory only when that update affects one row. The state change, stock restoration and activity event are committed together. Repeated cancellation is therefore safe.

## Authorization

Laravel policies control resource access. Sensitive service methods check authorization again, so hiding a button is not the only protection. Role changes are not mass assignable. There is no public registration endpoint or account administration interface in this version.

Staff can read operations data, place orders and cancel their own orders. Administrators can manage products and customers, adjust inventory and access reports and history. Report downloads require both administrator status and ownership of the report.

## Reporting

ReportService validates dates, saves the request and dispatches the job after the transaction commits. GenerateSalesReport queries confirmed orders in batches, escapes spreadsheet formula prefixes and writes to private local storage. A deterministic report path and a Ready status check make repeated delivery safe after successful completion. Queue overlap middleware prevents two workers processing the same report concurrently.

The report is generated from data available at execution time. It is not an accounting snapshot at request time. The queue worker must run independently from the web process.

## Deliberate limits

This project does not implement payments, tax calculations, partial fulfilment, returns, multiple warehouses, supplier purchasing, stock reservations or multi tenant isolation. It uses one currency. It is a demonstration of a coherent workflow, with documented production considerations rather than a claim of production readiness.
