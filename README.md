# Coffee Feed Writer

A CLI tool that reads a nested JSONL product feed, flattens it into tabular
rows (one row per product × variant), and writes the result to SQLite.

---

## How to run

### Prerequisites
- Docker and Docker Compose — nothing else required

### Steps

```bash
# 1. Clone the repository
git clone <repo-url>
cd coffee-pipeline

# 2. Run
docker compose up
```

The SQLite output file appears at `output/products.sqlite` on your machine
after the run completes.

### Run with a different feed file

```bash
docker compose run products-writer php artisan write:products --path=/data/other_feed.jsonl
```

### Run tests

```bash
# Run all tests
docker docker compose run --rm products-writer-test
```

---

## Design choices

### Hexagonal architecture

The codebase is split into three layers with a strict dependency rule — outer layers depend on inner layers, never the reverse.

Domain      ← pure business logic, zero framework dependencies
Application ← orchestration, coordinates domain and infrastructure
Infrastructure ← talks to the outside world (files, SQLite)

The practical benefit: swapping SQLite for CSV, Postgres, or any other output requires adding one new class that implements`ProductVariantRepository`. Nothing in the domain or application layer changes.

### DDD aggregate root

`Product` is the aggregate root. It holds business state, enforces invariants (`sku` and `name` are required).
The aggregate does not know about SQLite, logging, or any infrastructure concern.

### Command pattern

`FeedCommand` carries the input data. `FeedHandler` contains the logic. They are separate because a command is just a sealed envelope — it can be logged, queued, or replayed independently of the handler.

### ProductFlattenService as a domain service

Flattening a `Product` into `FlatRow[]` is pure business transformation — no I/O, no framework. It lives in the domain layer as a separate service rather than on the aggregate itself because `FlatRow` is a persistence shape, not a core business concept. The product should not know about SQLite column names.

### Generator in JsonlReader

`JsonlReader::read()` uses `yield` instead of building an array. Memory usage stays constant regardless of file size — a 500,000 product feed uses the same RAM as a 500 product feed. The `finally` block ensures the file handle is always closed even if the caller breaks early.

### Full reload strategy

The repository truncates the table before every run. Whatever is in the feed right now is what the database contains. This makes the command idempotent — running it twice gives the same result. The trade-off is that it is not suitable for incremental feeds. If the feed becomes incremental, the fix is one method change in the repository:replace `truncate()` with `upsert()` keyed on `variant_sku`.

### Products with no variants

Rather than skipping products that have no variants, the writer writes one stub row with null variant fields. This ensures the product is visible in the database.

---

## AI assistance disclosure

I used Claude (Anthropic) as a pair programming assistant throughout this project. Here is specifically where and how.

**Architecture and design decisions**
I used Claude to pressure-test my design decisions — specifically whether `flatten()` belongs on the aggregate or a domain service, whether logging
inside a domain object is acceptable. Claude's pushback led me to extract `ProductFlattenService`, and keep the domain layer free of any infrastructure concerns.

**Code generation**
I prompted Claude to draft the value objects (`Origin`, `Roast`, `TastingScore`), the `JsonlReader`, the repository implementation, and the CLI command structure. I directed the architecture and reviewed every line before including it.

**Tests**
I used Claude to generate the test files — `ProductTest`, `ProductFlattenServiceTest`, and `writePipelineTest`. I directed which behaviours to test and reviewed each test case to confirm it was testing something meaningful. I deliberately avoided coverage-for-coverage's-sake tests and focused on domain logic, transformation correctness, error tolerance, and data integrity.

**README**
This README was written with Claude's assistance. I provided the structure, the design decisions, and the trade-offs. Claude helped organise and phrase them clearly.

**Debugging**
I used Claude to diagnose several issues during development:
- Docker build failures (`artisan` not found during `composer install`)
- PHP version mismatch between `composer.lock` and the Docker image
- The `routes/console.php` closure shadowing the `ProcessFeed` command
- SQLite transaction not committing — rows counted but not saved
- Migration conflicts from stale entries in the migrations table

**What I directed myself**
All architectural decisions, layer boundaries, the choice of full reload vs upsert, the stub row approach for products with no variants, the naming conventions, and the test selection were my own decisions. I used Claude to implement and validate them, not to make them. Every bug I hit, I debugged with Claude as a tool — I identified the symptoms asked the right questions, and understood the fixes before applying them.