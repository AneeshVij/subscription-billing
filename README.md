# Subscription Billing & Usage Metering System

A Laravel-based multi-tenant SaaS subscription billing and usage-metering system.

The application supports merchants, plans, customers, subscriptions, usage events, metered billing, overage calculation, mid-cycle plan changes, invoicing, dashboards, idempotent usage ingestion, rate limiting, and asynchronous usage aggregation.

---

1. Technology Stack

- PHP 8.5
- Laravel 13
- MariaDB / MySQL
- Blade
- Laravel Queue
- Eloquent ORM
- REST API
- PHPUnit / Laravel testing framework

---

2. Main Features

- Multi-tenant merchant management
- Plan management
- Customer management
- Subscription management
- Mid-cycle plan upgrades and downgrades
- Usage metering
- Idempotent usage ingestion
- API rate limiting
- Daily usage aggregation
- Asynchronous queue processing
- Overage billing
- Prorated billing
- Invoice generation
- Customer usage dashboard
- Merchant-specific dashboard
- Database indexing for large datasets

---

3. Merchant Management

Merchants can be created and managed independently.

Each merchant owns:

- Plans
- Customers
- Subscriptions
- Usage events
- Invoices

Tenant ownership is enforced through foreign keys and application-level validation.

---

4. Plan Management

Each plan contains:

- Plan name
- Base price
- Billing cycle
- Included usage units
- Overage rate

Example:

```text
Plan: Pro
Base price: ₹1,000
Billing cycle: Monthly
Included units: 10,000
Overage rate: ₹0.50/unit

5. Customer Management

Customers belong to a merchant.

A customer cannot be subscribed to a plan belonging to another merchant.

6. Subscription Management

A customer can subscribe to a merchant plan.

Subscription information includes:

Customer
Plan
Start date
End date
Status
7. Subscription Plan Changes

Mid-cycle plan changes are supported.

The subscription_changes table stores:

Subscription
Old plan
New plan
Effective date

The original and new plan information is retained so that historical billing can be calculated correctly.

Example:

1 Sep - 15 Sep
Basic Plan

16 Sep - 30 Sep
Pro Plan

Billing is calculated separately for each segment.

8. Usage Metering

Usage is recorded through:

POST /api/usage

Example request:

{
    "merchant_id": 1,
    "customer_id": 1,
    "idempotency_key": "usage-10001",
    "usage_date": "2026-09-22",
    "units": 500,
    "metadata": {
        "source": "api"
    }
}

Usage events are stored in the usage_events table.

9. Idempotency

Usage ingestion is idempotent.

The database has a composite unique constraint:

merchant_id + idempotency_key

This prevents the same usage request from creating duplicate usage events for the same merchant.

Flow:

Request
   ↓
Check existing idempotency key
   ↓
If found → return existing event
   ↓
Otherwise create event
   ↓
Database UNIQUE constraint protects concurrent requests

The database constraint provides the final protection against concurrent duplicate requests.

10. Rate Limiting

The usage API uses Laravel's rate limiter.

Current configuration:

60 requests per minute

The rate limit is primarily scoped by merchant ID.

This helps prevent a single merchant from overwhelming the usage ingestion endpoint.

11. Daily Usage Aggregation

Raw usage events can become very large.

Instead of using the raw usage_events table for every dashboard query, daily usage is aggregated into:

daily_usages

The AggregateDailyUsageJob processes usage asynchronously.

Flow:

POST /api/usage
       ↓
usage_events
       ↓
Queue
       ↓
AggregateDailyUsageJob
       ↓
DailyUsage

The aggregation job uses:

chunkById(1000)

This prevents millions of records from being loaded into memory at once.

The job can also be safely rerun for a merchant/date because the daily aggregate is rebuilt for that date.

12. Queue Architecture

Laravel queues are used for expensive asynchronous processing.

Usage Aggregation
UsageController
      ↓
UsageEvent created
      ↓
AggregateDailyUsageJob dispatched
      ↓
Queue Worker
      ↓
DailyUsage
Cycle-End Invoicing

Invoice generation is also processed asynchronously.

The command:

php artisan billing:generate-invoices 2026-09-01 2026-09-30

queues:

GenerateCycleInvoicesJob

The job processes subscriptions using:

chunkById(100)

Each subscription is passed to:

BillingService

This keeps billing calculations separate from queue orchestration.

13. Billing Calculation

The billing service calculates:

Base subscription amount
+
Overage amount
=
Invoice total

Overage is calculated as:

Usage units - Included units

Only positive overage is billed.

Example:

Included units = 1,000
Actual usage   = 1,200
Overage units  = 200

Overage rate   = ₹2/unit

Overage amount = ₹400
14. Proration

Mid-cycle subscription changes are prorated according to the number of days in each plan segment.

Example:

Monthly billing period = 30 days

Basic plan:
15 days

Pro plan:
15 days

The billing service calculates the base amount and included units proportionally for each segment.

This supports upgrades and downgrades during an active billing cycle.

15. Invoice Structure

Invoices contain:

Merchant
Customer
Subscription
Billing period
Base amount
Overage amount
Total amount
Status

Invoice items contain:

Plan
Description
Usage units
Included units
Rate
Amount
Period start
Period end

This preserves billing details for each plan segment.

16. Dashboard

The dashboard provides:

Top 5 Customers by Usage

The system identifies customers with the highest usage during the current month.

Projected Overage Revenue

The dashboard calculates projected overage revenue based on:

Current usage
Included plan units
Overage rate
Usage Drop Detection

Customers whose usage has dropped by more than 50% compared with the previous month are identified.

17. Merchant Dashboard

A merchant-specific dashboard is available through:

GET /merchants/{merchant}/dashboard

Example:

GET /merchants/1/dashboard

The dashboard only uses data belonging to the requested merchant.

This prevents cross-tenant data exposure.

18. Database Design

Main tables:

merchants
    |
    +--- plans
    |
    +--- customers
            |
            +--- subscriptions
            |
            +--- usage_events
            |
            +--- invoices
                    |
                    +--- invoice_items

subscriptions
    |
    +--- subscription_changes

usage_events
    |
    +--- daily_usages

Foreign keys are used to maintain referential integrity.

19. Important Indexes

The system uses indexes for common query patterns.

Usage Events
merchant_id + usage_date
customer_id + usage_date
merchant_id + customer_id + usage_date
merchant_id + idempotency_key
Subscriptions
customer_id + status
plan_id + starts_at
Plans
merchant_id + billing_cycle
Daily Usage
merchant_id + customer_id + usage_date

These indexes reduce the amount of data that must be scanned for common dashboard, billing, and aggregation queries.

20. Scaling to 50L+ Usage Events

For approximately 5 crore or more usage events, the raw usage table should not be used directly for every analytical query.

The system uses daily aggregation as the first scalability layer:

5 crore raw usage events
          ↓
Daily aggregation
          ↓
DailyUsage
          ↓
Dashboard / reporting

Additional production scaling strategies include:

1. Partitioning

Partition usage events by date or another suitable tenant/time strategy.

This allows old partitions to be archived efficiently.

2. Batch Processing

Use:

chunkById()

for large record processing.

3. Queue Workers

Run multiple queue workers for:

Daily aggregation
Invoice generation
Other background processing
4. Read-Optimized Aggregates

Dashboards should read from aggregate tables rather than repeatedly scanning the raw usage event table.

5. Archiving

Old usage events can be moved to archive storage after the required retention period.

21. Caching Strategy

Plan and pricing data changes less frequently than usage data.

In production, plan lookups can be cached using Laravel Cache:

Cache::remember(
    "plan:{$planId}",
    3600,
    fn () => Plan::findOrFail($planId)
);

The cache should be invalidated when a plan is updated or deleted.

Usage events should not be cached as the primary source of truth.

22. Separation of Concerns

The application separates responsibilities:

Controllers
    ↓
Request validation / HTTP responses

Services
    ↓
Business logic

Jobs
    ↓
Asynchronous processing

Models
    ↓
Database relationships

Database
    ↓
Persistence / constraints / indexes

The main billing calculations are kept inside:

BillingService

rather than inside controllers or queue jobs.

23. API Endpoints
Usage
POST /api/usage

Records usage.

Merchant Dashboard
GET /merchants/{merchant}/dashboard

Returns merchant-specific dashboard data.

24. Queue Commands

Start the queue worker:

php artisan queue:work

Generate invoices for a billing cycle:

php artisan billing:generate-invoices 2026-09-01 2026-09-30
25. Local Setup

Install dependencies:

composer install

Configure .env:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=subscription_billing
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

Run migrations:

php artisan migrate

Clear cached configuration:

php artisan optimize:clear

Start Laravel:

php artisan serve

Start the queue worker:

php artisan queue:work

Application:

http://127.0.0.1:8000
26. Design Trade-offs
Raw Events vs Aggregate Table

Raw events are retained as the source of truth because they provide an audit trail.

Daily aggregates are used for faster reporting and dashboard queries.

Synchronous vs Asynchronous Processing

Usage ingestion remains lightweight while aggregation and invoice generation are processed asynchronously.

This allows the API to respond without waiting for expensive reporting or billing calculations.

Database Constraints vs Application Checks

Application checks provide friendly validation.

Database constraints provide final data integrity and concurrency protection.

Billing Service

Billing calculations are centralized in a service so the same logic can be used from:

HTTP requests
Queue jobs
Scheduled commands
Future billing workflows
27. Production Improvements

For a larger production deployment, the following could be added:

Redis queue
Redis cache
Multiple queue workers
MySQL/MariaDB partitioning
Read replicas
Dedicated analytics database
Monitoring and queue failure alerts
Retry/backoff policies
Archived usage storage
Scheduled daily aggregation
Scheduled cycle-end billing
More comprehensive automated tests
28. Summary

The application provides a complete subscription billing and usage-metering workflow:

Merchant
   ↓
Plan
   ↓
Customer
   ↓
Subscription
   ↓
Usage Events
   ↓
Daily Aggregation
   ↓
Billing Service
   ↓
Invoice
   ↓
Dashboard

The architecture uses database constraints, indexes, idempotency, rate limiting, queues, chunked processing, aggregation, and service-based billing logic to provide a foundation that can scale beyond the initial dataset.