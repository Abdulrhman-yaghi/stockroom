# Verification record

## Verified locally

The application passed 20 PHP tests with 63 assertions and four browser tests on the implementation used for this preview.

The PHP suite verifies order totals from stored prices, stock deduction, rollback of partial work, cancellation exactly once, administrator and staff policies, report date validation, exclusion of cancelled orders, safe spreadsheet cells, report retry and owner checked downloads.

Livewire tests submit the real order and stock adjustment actions. A separate concurrency test launches two PHP processes against the same SQLite database containing one unit. One process creates an order, the other receives a validation rejection, and stock ends at zero.

The browser suite signs into the application, places and cancels an order, requests a report, waits for the running queue worker and downloads the resulting CSV. It also checks staff restrictions, captures the main screens, checks the mobile guide at 390 pixels and runs an axe accessibility scan of the guide. No browser runtime errors were observed while visiting the recorded screens.

The frontend production build completed successfully. Laravel Pint formatted the PHP implementation. Composer reported no known security advisories at installation time.

## What this does not establish

These checks do not establish production capacity, a full security audit, support for other database engines or complete accessibility compliance. The race test covers a two buyer SQLite scenario, not sustained production contention. The axe scan covers the guide, not every possible modal and table state.

The public preview is static. The PHP application and queue worker were tested locally. No public PHP deployment is claimed.

## Reproduce

Run the setup steps in the README, then execute:

```sh
composer test
npm run test:browser
```

Browser tests need the demo database and queue worker. They change only fictional local demo records. PHP tests use isolated test databases.

GitHub Actions also runs the PHP suite, frontend build and browser tests. Its status badge or run page is the source of truth for the latest remote run.
