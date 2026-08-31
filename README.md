# Laravel 13 + SAP HANA Eloquent Reporting
This project uses Laravel 13 Eloquent with SAP HANA through the `pdo_odbc` / `HDBODBC` driver.
The implementation is designed primarily for **read-only reporting**, not SAP transaction creation, updating, or deletion.
---
# 1. SAP HANA Database Configuration
In `config/database.php`:
```php
'saphana' => [
            'driver' => 'saphana',
            'dsn' => 'odbc:DRIVER={HDBODBC};SERVERNODE=' . env('HANA_HOST') . ':' . env('HANA_PORT', 30015) . ';DATABASE=' . env('HANA_DATABASE') . ';CHAR_AS_UTF8=TRUE',
            'host' => env('HANA_HOST'),
            'port' => env('HANA_PORT', 30015),
            'database' => env('HANA_DATABASE'),
            'username' => env('HANA_USERNAME'),
            'password' => env('HANA_PASSWORD'),
            'prefix' => '',
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                /*
                    * We don't use PDO prepared statements in HanaConnection,
                    * so this setting is not important for query execution.
                */
                \PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ],
``` 

# 2. Important Note: Avoid `SELECT *`
When using Laravel Eloquent with SAP HANA through PDO_ODBC / HDBODBC, **avoid `SELECT *` whenever possible**.
## Why?
Using:
```php
BusinessPartner::query()->get();
or
BusinessPartner::query()->lazy();
```
without specifying select() causes Laravel to generate:
``` php
SELECT * FROM "TNLOIL_V051_01"."OCRD"

```
SAP Business One tables can contain many columns with different data types, including:
NVARCHAR
VARCHAR
INTEGER
DECIMAL
DATE
TIME
TIMESTAMP
BLOB / binary-related fields
Large text fields
Other SAP HANA-specific data types

Some columns may not be returned by HDBODBC in a format that PHP can safely process as UTF-8.

This can cause errors such as:
``` php
Malformed UTF-8 characters, possibly incorrectly encoded
```
## Recommended Approach
Always select only the columns required by the report.
``` php
$data = BusinessPartner::query()
    ->select([
        'CardCode',
        'CardName',
        'CreateDate',
    ])
    ->orderBy('CardCode')
    ->lazy()
    ->take(10)
    ->all();
```
# 3. SAP Eloquent / Report Controller Testing
## Purpose

This document provides a simple way to test the Laravel routes supplied in the source file.

The supplied source defines two route groups:

- `report/*` → `SapReportController`
- `sap/*` → `SapEloquentTestController`

The source contains **115 route declarations**, with the `/sap` group appearing twice. This README treats duplicate declarations as one logical endpoint. The duplicate route block should normally be removed from the actual route file to avoid confusion.

Source reference: the report routes are defined under the `report` prefix and call methods such as `testJoin`, `testLeftJoin`, and `testWhereDate`. fileciteturn0file0L5-L15

## 1. Prerequisites

Before testing:

1. Start the Laravel application.
2. Confirm the database connection in `.env`.
3. Confirm the required tables/data exist.
4. Confirm both controllers exist:
   - `App\Http\Controllers\Api\V1\SapReportController`
   - `App\Http\Controllers\Api\V1\SapEloquentTestController`
5. Clear route/cache if routes were recently changed:

```bash
php artisan optimize:clear
```

6. Start the application if needed:

```bash
php artisan serve
```

Example base URL:

```text
http://127.0.0.1:8000
```

> If these routes are registered in `routes/api.php`, Laravel normally exposes them under `/api`. If they are registered in `web.php`, use the route without `/api`. Check `php artisan route:list` to confirm the actual URL.

## 2. Confirm the Routes

Run:

```bash
php artisan route:list
```

For a quick filter:

```bash
php artisan route:list | findstr /I "sap report"
```

Expected route families:

```text
GET  /.../report/join
GET  /.../report/left-join
GET  /.../report/join-sub
...
GET  /.../sap/get
GET  /.../sap/first
GET  /.../sap/find
...
```

## 3. Basic Testing

Use a browser, Postman, Insomnia, curl, or another HTTP client.

Example:

```http
GET {BASE_URL}/api/sap/get
```

No request body is shown in the supplied route file. The exact query parameters required by each controller method are **not available in the supplied source**, so use the controller implementation to confirm parameter names before adding them.

## 4. Recommended Test Result

For every endpoint, record:

| Field | Value |
|---|---|
| Test Case ID | TC-001 |
| Method | GET |
| URL | Actual route URL |
| Parameters | Actual parameters used |
| Expected | Expected behavior |
| Actual | Actual response |
| HTTP Status | 200 / 4xx / 5xx |
| Result | PASS / FAIL |
| Notes | Error or observation |

## 5. Important Safety Check

These are test/report endpoints. Test them against a development or test database first.

Do not assume an endpoint is read-only only from the route declaration. The supplied routes use `GET`, but the actual controller implementation determines what the method does.

## 6. Test Coverage

### Report Controller

| # | Endpoint | Controller Method | Expected |
|---:|---|---|---|
| 1 | `GET /report/join` | `testJoin()` | Returns records from the configured tables using an INNER JOIN. |
| 2 | `GET /report/left-join` | `testLeftJoin()` | Returns records using a LEFT JOIN, including matching and non-matching left-side rows where applicable. |
| 3 | `GET /report/join-sub` | `testJoinSub()` | Returns results using a JOIN against a subquery. |
| 4 | `GET /report/group-by` | `testGroupBy()` | Returns grouped report results. |
| 5 | `GET /report/having` | `testHaving()` | Returns grouped results filtered with HAVING. |
| 6 | `GET /report/where-date` | `testWhereDate()` | Returns records filtered by date. |
| 7 | `GET /report/where-in` | `testWhereIn()` | Returns records matching values supplied to WHERE IN. |
| 8 | `GET /report/where-exists` | `testWhereExists()` | Returns records where the configured related/existence condition is true. |
| 9 | `GET /report/where-not-exists` | `testWhereNotExists()` | Returns records where the configured related/existence condition is false. |
| 10 | `GET /report/union` | `testUnion()` | Returns the combined result of the configured UNION queries. |
| 11 | `GET /report/distinct` | `testDistinct()` | Returns unique/distinct results. |
| 12 | `GET /report/select-raw` | `testSelectRaw()` | Returns results using a raw SELECT expression. |
| 13 | `GET /report/order-by-raw` | `testOrderByRaw()` | Returns results ordered using a raw ORDER BY expression. |
| 14 | `GET /report/group-by-raw` | `testGroupByRaw()` | Returns grouped results using a raw GROUP BY expression. |
| 15 | `GET /report/having-raw` | `testHavingRaw()` | Returns grouped results filtered using a raw HAVING expression. |

### SAP Eloquent Test Controller

| # | Endpoint | Controller Method | Expected |
|---:|---|---|---|
| 1 | `GET /sap/get` | `get()` | Returns the available records. |
| 2 | `GET /sap/first` | `first()` | Returns the first matching record or null/no result when no record matches. |
| 3 | `GET /sap/find` | `find()` | Returns the record for the supplied primary key. |
| 4 | `GET /sap/where` | `where()` | Returns records matching the WHERE condition. |
| 5 | `GET /sap/where-multiple` | `whereMultiple()` | Returns records matching multiple WHERE conditions. |
| 6 | `GET /sap/or-where` | `orWhere()` | Returns records matching either the primary or OR condition. |
| 7 | `GET /sap/where-in` | `whereIn()` | Returns records whose selected column is in the supplied list. |
| 8 | `GET /sap/where-not-in` | `whereNotIn()` | Returns records whose selected column is not in the supplied list. |
| 9 | `GET /sap/where-null` | `whereNull()` | Returns records where the selected column is NULL. |
| 10 | `GET /sap/where-not-null` | `whereNotNull()` | Returns records where the selected column is not NULL. |
| 11 | `GET /sap/where-like` | `whereLike()` | Returns records matching a LIKE condition. |
| 12 | `GET /sap/where-not-like` | `whereNotLike()` | Returns records that do not match a LIKE condition. |
| 13 | `GET /sap/where-between` | `whereBetween()` | Returns records whose selected value is within the specified range. |
| 14 | `GET /sap/where-not-between` | `whereNotBetween()` | Returns records outside the specified range. |
| 15 | `GET /sap/order-by-asc` | `orderByAsc()` | Returns records ordered ascending. |
| 16 | `GET /sap/order-by-desc` | `orderByDesc()` | Returns records ordered descending. |
| 17 | `GET /sap/limit` | `limit()` | Returns no more than the configured number of records. |
| 18 | `GET /sap/offset` | `offset()` | Skips the configured number of records before returning results. |
| 19 | `GET /sap/limit-offset` | `limitOffset()` | Applies both LIMIT and OFFSET. |
| 20 | `GET /sap/paginate` | `paginate()` | Returns paginated results with pagination metadata. |
| 21 | `GET /sap/simple-paginate` | `simplePaginate()` | Returns simple paginated results. |
| 22 | `GET /sap/count` | `count()` | Returns the number of matching records. |
| 23 | `GET /sap/exists` | `exists()` | Returns whether at least one matching record exists. |
| 24 | `GET /sap/doesnt-exist` | `doesntExist()` | Returns whether no matching record exists. |
| 25 | `GET /sap/value` | `value()` | Returns a single column value from the matching record. |
| 26 | `GET /sap/pluck` | `pluck()` | Returns values from the selected column. |
| 27 | `GET /sap/distinct` | `distinct()` | Returns distinct/unique values or records. |
| 28 | `GET /sap/group-by` | `groupBy()` | Returns grouped query results. |
| 29 | `GET /sap/max` | `max()` | Returns the maximum value for the selected column. |
| 30 | `GET /sap/min` | `min()` | Returns the minimum value for the selected column. |
| 31 | `GET /sap/avg` | `avg()` | Returns the average value for the selected column. |
| 32 | `GET /sap/sum` | `sum()` | Returns the sum for the selected column. |
| 33 | `GET /sap/first-or-fail` | `firstOrFail()` | Returns the first matching record; a not-found condition should be handled as an error. |
| 34 | `GET /sap/find-many` | `findMany()` | Returns multiple records for the supplied primary keys. |
| 35 | `GET /sap/when` | `when()` | Applies a conditional query clause when the configured condition is true. |
| 36 | `GET /sap/where-column` | `whereColumn()` | Compares one database column with another column. |
| 37 | `GET /sap/raw-select` | `rawSelect()` | Returns results using a raw SELECT expression. |
| 38 | `GET /sap/raw-where` | `rawWhere()` | Returns results using a raw WHERE expression. |
| 39 | `GET /sap/join` | `join()` | Returns records from a query using a JOIN. |
| 40 | `GET /sap/reorder` | `reorder()` | Returns records after replacing the default ordering. |
| 41 | `GET /sap/take` | `take()` | Returns the configured number of records. |
| 42 | `GET /sap/skip` | `skip()` | Skips the configured number of records. |
| 43 | `GET /sap/chunk` | `chunk()` | Processes/returns records in chunks. |
| 44 | `GET /sap/chunk-by-id` | `chunkById()` | Processes/returns records in ID-based chunks. |
| 45 | `GET /sap/cursor` | `cursor()` | Returns/iterates records using a cursor. |
| 46 | `GET /sap/lazy` | `lazy()` | Returns/iterates records lazily. |
| 47 | `GET /sap/lazy-by-id` | `lazyById()` | Returns/iterates records lazily by ID. |
| 48 | `GET /sap/pluck-collection` | `pluckCollection()` | Returns a plucked collection. |
| 49 | `GET /sap/to-base` | `toBase()` | Returns the query using the base query builder. |
| 50 | `GET /sap/binding` | `binding()` | Returns query binding information for inspection. |

## 7. Troubleshooting

### 404 Not Found

Run:

```bash
php artisan route:list
```

Then verify the prefix (`/api`, `/web`, or another configured prefix).

### Controller not found

Verify the namespace and class:

```php
use App\Http\Controllers\Api\V1\SapReportController;
use App\Http\Controllers\Api\V1\SapEloquentTestController;
```

### Database error

Check `.env`:

```env
DB_CONNECTION=
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Then:

```bash
php artisan optimize:clear
```

### Duplicate route

The supplied source contains the `/sap` route group twice. Keep only one copy of that group in the actual route file.

## 8. Suggested Testing Order

Start with the simplest endpoints:

1. `/sap/get`
2. `/sap/first`
3. `/sap/find`
4. `/sap/where`
5. `/sap/count`
6. `/sap/exists`
7. `/sap/where-in`
8. `/sap/order-by-asc`
9. `/sap/paginate`
10. `/sap/join`
11. `/report/join`
12. `/report/left-join`
13. `/report/group-by`
14. `/report/having`
15. Raw-query tests last

This makes troubleshooting easier because basic query behavior can be confirmed before testing joins, grouping, pagination, and raw SQL.

## 9. Full Test Checklist

- [ ] Application starts successfully
- [ ] Database connection works
- [ ] Routes appear in `php artisan route:list`
- [ ] `/sap/get` works
- [ ] Basic filtering works
- [ ] Sorting works
- [ ] Pagination works
- [ ] Aggregate functions work
- [ ] JOIN works
- [ ] GROUP BY works
- [ ] HAVING works
- [ ] Raw SELECT works
- [ ] Raw WHERE works
- [ ] Chunk/cursor/lazy methods work
- [ ] Error cases are handled correctly
- [ ] Duplicate `/sap` route group removed


# 4. TESTING_SAP_ELOQUENT
# TESTING GUIDE

## Document Purpose

This is a user-friendly test checklist for the Laravel SAP Eloquent and report test controllers.

**Total logical endpoints:** 65

- **Report:** 15
- **SAP Eloquent:** 50

> The supplied source contains the `/sap` route group twice. Test each logical endpoint once and remove the duplicate route block from the application route file.

---

## Test Environment

| Item | Value |
|---|---|
| Application URL | `{BASE_URL}` |
| HTTP Method | `GET` |
| Database | Development/Test DB |
| Client | Browser / Postman / Insomnia |
| Authentication | Verify application configuration |
| Date Tested | __________ |
| Tester | __________ |

## How to Start

```bash
php artisan optimize:clear
php artisan route:list
php artisan serve
```

Find the actual route prefix before testing.

---

# A. SAP Eloquent Tests

## TC-SAP-001 — Get

**Request**

```http
GET {BASE_URL}/api/sap/get
```

**Controller method**

```php
SapEloquentTestController::get()
```

**Expected result**

Returns the available records.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-002 — First

**Request**

```http
GET {BASE_URL}/api/sap/first
```

**Controller method**

```php
SapEloquentTestController::first()
```

**Expected result**

Returns the first matching record or null/no result when no record matches.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-003 — Find

**Request**

```http
GET {BASE_URL}/api/sap/find
```

**Controller method**

```php
SapEloquentTestController::find()
```

**Expected result**

Returns the record for the supplied primary key.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-004 — Where

**Request**

```http
GET {BASE_URL}/api/sap/where
```

**Controller method**

```php
SapEloquentTestController::where()
```

**Expected result**

Returns records matching the WHERE condition.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-005 — Where Multiple

**Request**

```http
GET {BASE_URL}/api/sap/where-multiple
```

**Controller method**

```php
SapEloquentTestController::whereMultiple()
```

**Expected result**

Returns records matching multiple WHERE conditions.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-006 — Or Where

**Request**

```http
GET {BASE_URL}/api/sap/or-where
```

**Controller method**

```php
SapEloquentTestController::orWhere()
```

**Expected result**

Returns records matching either the primary or OR condition.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-007 — Where In

**Request**

```http
GET {BASE_URL}/api/sap/where-in
```

**Controller method**

```php
SapEloquentTestController::whereIn()
```

**Expected result**

Returns records whose selected column is in the supplied list.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-008 — Where Not In

**Request**

```http
GET {BASE_URL}/api/sap/where-not-in
```

**Controller method**

```php
SapEloquentTestController::whereNotIn()
```

**Expected result**

Returns records whose selected column is not in the supplied list.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-009 — Where Null

**Request**

```http
GET {BASE_URL}/api/sap/where-null
```

**Controller method**

```php
SapEloquentTestController::whereNull()
```

**Expected result**

Returns records where the selected column is NULL.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-010 — Where Not Null

**Request**

```http
GET {BASE_URL}/api/sap/where-not-null
```

**Controller method**

```php
SapEloquentTestController::whereNotNull()
```

**Expected result**

Returns records where the selected column is not NULL.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-011 — Where Like

**Request**

```http
GET {BASE_URL}/api/sap/where-like
```

**Controller method**

```php
SapEloquentTestController::whereLike()
```

**Expected result**

Returns records matching a LIKE condition.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-012 — Where Not Like

**Request**

```http
GET {BASE_URL}/api/sap/where-not-like
```

**Controller method**

```php
SapEloquentTestController::whereNotLike()
```

**Expected result**

Returns records that do not match a LIKE condition.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-013 — Where Between

**Request**

```http
GET {BASE_URL}/api/sap/where-between
```

**Controller method**

```php
SapEloquentTestController::whereBetween()
```

**Expected result**

Returns records whose selected value is within the specified range.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-014 — Where Not Between

**Request**

```http
GET {BASE_URL}/api/sap/where-not-between
```

**Controller method**

```php
SapEloquentTestController::whereNotBetween()
```

**Expected result**

Returns records outside the specified range.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-015 — Order By Asc

**Request**

```http
GET {BASE_URL}/api/sap/order-by-asc
```

**Controller method**

```php
SapEloquentTestController::orderByAsc()
```

**Expected result**

Returns records ordered ascending.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-016 — Order By Desc

**Request**

```http
GET {BASE_URL}/api/sap/order-by-desc
```

**Controller method**

```php
SapEloquentTestController::orderByDesc()
```

**Expected result**

Returns records ordered descending.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-017 — Limit

**Request**

```http
GET {BASE_URL}/api/sap/limit
```

**Controller method**

```php
SapEloquentTestController::limit()
```

**Expected result**

Returns no more than the configured number of records.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-018 — Offset

**Request**

```http
GET {BASE_URL}/api/sap/offset
```

**Controller method**

```php
SapEloquentTestController::offset()
```

**Expected result**

Skips the configured number of records before returning results.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-019 — Limit Offset

**Request**

```http
GET {BASE_URL}/api/sap/limit-offset
```

**Controller method**

```php
SapEloquentTestController::limitOffset()
```

**Expected result**

Applies both LIMIT and OFFSET.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-020 — Paginate

**Request**

```http
GET {BASE_URL}/api/sap/paginate
```

**Controller method**

```php
SapEloquentTestController::paginate()
```

**Expected result**

Returns paginated results with pagination metadata.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-021 — Simple Paginate

**Request**

```http
GET {BASE_URL}/api/sap/simple-paginate
```

**Controller method**

```php
SapEloquentTestController::simplePaginate()
```

**Expected result**

Returns simple paginated results.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-022 — Count

**Request**

```http
GET {BASE_URL}/api/sap/count
```

**Controller method**

```php
SapEloquentTestController::count()
```

**Expected result**

Returns the number of matching records.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-023 — Exists

**Request**

```http
GET {BASE_URL}/api/sap/exists
```

**Controller method**

```php
SapEloquentTestController::exists()
```

**Expected result**

Returns whether at least one matching record exists.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-024 — Doesnt Exist

**Request**

```http
GET {BASE_URL}/api/sap/doesnt-exist
```

**Controller method**

```php
SapEloquentTestController::doesntExist()
```

**Expected result**

Returns whether no matching record exists.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-025 — Value

**Request**

```http
GET {BASE_URL}/api/sap/value
```

**Controller method**

```php
SapEloquentTestController::value()
```

**Expected result**

Returns a single column value from the matching record.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-026 — Pluck

**Request**

```http
GET {BASE_URL}/api/sap/pluck
```

**Controller method**

```php
SapEloquentTestController::pluck()
```

**Expected result**

Returns values from the selected column.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-027 — Distinct

**Request**

```http
GET {BASE_URL}/api/sap/distinct
```

**Controller method**

```php
SapEloquentTestController::distinct()
```

**Expected result**

Returns distinct/unique values or records.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-028 — Group By

**Request**

```http
GET {BASE_URL}/api/sap/group-by
```

**Controller method**

```php
SapEloquentTestController::groupBy()
```

**Expected result**

Returns grouped query results.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-029 — Max

**Request**

```http
GET {BASE_URL}/api/sap/max
```

**Controller method**

```php
SapEloquentTestController::max()
```

**Expected result**

Returns the maximum value for the selected column.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-030 — Min

**Request**

```http
GET {BASE_URL}/api/sap/min
```

**Controller method**

```php
SapEloquentTestController::min()
```

**Expected result**

Returns the minimum value for the selected column.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-031 — Avg

**Request**

```http
GET {BASE_URL}/api/sap/avg
```

**Controller method**

```php
SapEloquentTestController::avg()
```

**Expected result**

Returns the average value for the selected column.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-032 — Sum

**Request**

```http
GET {BASE_URL}/api/sap/sum
```

**Controller method**

```php
SapEloquentTestController::sum()
```

**Expected result**

Returns the sum for the selected column.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-033 — First Or Fail

**Request**

```http
GET {BASE_URL}/api/sap/first-or-fail
```

**Controller method**

```php
SapEloquentTestController::firstOrFail()
```

**Expected result**

Returns the first matching record; a not-found condition should be handled as an error.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-034 — Find Many

**Request**

```http
GET {BASE_URL}/api/sap/find-many
```

**Controller method**

```php
SapEloquentTestController::findMany()
```

**Expected result**

Returns multiple records for the supplied primary keys.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-035 — When

**Request**

```http
GET {BASE_URL}/api/sap/when
```

**Controller method**

```php
SapEloquentTestController::when()
```

**Expected result**

Applies a conditional query clause when the configured condition is true.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-036 — Where Column

**Request**

```http
GET {BASE_URL}/api/sap/where-column
```

**Controller method**

```php
SapEloquentTestController::whereColumn()
```

**Expected result**

Compares one database column with another column.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-037 — Raw Select

**Request**

```http
GET {BASE_URL}/api/sap/raw-select
```

**Controller method**

```php
SapEloquentTestController::rawSelect()
```

**Expected result**

Returns results using a raw SELECT expression.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-038 — Raw Where

**Request**

```http
GET {BASE_URL}/api/sap/raw-where
```

**Controller method**

```php
SapEloquentTestController::rawWhere()
```

**Expected result**

Returns results using a raw WHERE expression.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-039 — Join

**Request**

```http
GET {BASE_URL}/api/sap/join
```

**Controller method**

```php
SapEloquentTestController::join()
```

**Expected result**

Returns records from a query using a JOIN.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-040 — Reorder

**Request**

```http
GET {BASE_URL}/api/sap/reorder
```

**Controller method**

```php
SapEloquentTestController::reorder()
```

**Expected result**

Returns records after replacing the default ordering.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-041 — Take

**Request**

```http
GET {BASE_URL}/api/sap/take
```

**Controller method**

```php
SapEloquentTestController::take()
```

**Expected result**

Returns the configured number of records.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-042 — Skip

**Request**

```http
GET {BASE_URL}/api/sap/skip
```

**Controller method**

```php
SapEloquentTestController::skip()
```

**Expected result**

Skips the configured number of records.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-043 — Chunk

**Request**

```http
GET {BASE_URL}/api/sap/chunk
```

**Controller method**

```php
SapEloquentTestController::chunk()
```

**Expected result**

Processes/returns records in chunks.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-044 — Chunk By Id

**Request**

```http
GET {BASE_URL}/api/sap/chunk-by-id
```

**Controller method**

```php
SapEloquentTestController::chunkById()
```

**Expected result**

Processes/returns records in ID-based chunks.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-045 — Cursor

**Request**

```http
GET {BASE_URL}/api/sap/cursor
```

**Controller method**

```php
SapEloquentTestController::cursor()
```

**Expected result**

Returns/iterates records using a cursor.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-046 — Lazy

**Request**

```http
GET {BASE_URL}/api/sap/lazy
```

**Controller method**

```php
SapEloquentTestController::lazy()
```

**Expected result**

Returns/iterates records lazily.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-047 — Lazy By Id

**Request**

```http
GET {BASE_URL}/api/sap/lazy-by-id
```

**Controller method**

```php
SapEloquentTestController::lazyById()
```

**Expected result**

Returns/iterates records lazily by ID.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-048 — Pluck Collection

**Request**

```http
GET {BASE_URL}/api/sap/pluck-collection
```

**Controller method**

```php
SapEloquentTestController::pluckCollection()
```

**Expected result**

Returns a plucked collection.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-049 — To Base

**Request**

```http
GET {BASE_URL}/api/sap/to-base
```

**Controller method**

```php
SapEloquentTestController::toBase()
```

**Expected result**

Returns the query using the base query builder.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-SAP-050 — Binding

**Request**

```http
GET {BASE_URL}/api/sap/binding
```

**Controller method**

```php
SapEloquentTestController::binding()
```

**Expected result**

Returns query binding information for inspection.

**Test steps**

1. Open the URL in Postman/browser.
2. Add only the parameters required by the controller implementation.
3. Send the request.
4. Check HTTP status and response body.
5. Compare the returned data with the expected database result.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

# B. Report Tests

## TC-REPORT-001 — Join

**Request**

```http
GET {BASE_URL}/api/report/join
```

**Controller method**

```php
SapReportController::testJoin()
```

**Expected result**

Returns records from the configured tables using an INNER JOIN.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-002 — Left Join

**Request**

```http
GET {BASE_URL}/api/report/left-join
```

**Controller method**

```php
SapReportController::testLeftJoin()
```

**Expected result**

Returns records using a LEFT JOIN, including matching and non-matching left-side rows where applicable.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-003 — Join Sub

**Request**

```http
GET {BASE_URL}/api/report/join-sub
```

**Controller method**

```php
SapReportController::testJoinSub()
```

**Expected result**

Returns results using a JOIN against a subquery.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-004 — Group By

**Request**

```http
GET {BASE_URL}/api/report/group-by
```

**Controller method**

```php
SapReportController::testGroupBy()
```

**Expected result**

Returns grouped report results.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-005 — Having

**Request**

```http
GET {BASE_URL}/api/report/having
```

**Controller method**

```php
SapReportController::testHaving()
```

**Expected result**

Returns grouped results filtered with HAVING.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-006 — Where Date

**Request**

```http
GET {BASE_URL}/api/report/where-date
```

**Controller method**

```php
SapReportController::testWhereDate()
```

**Expected result**

Returns records filtered by date.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-007 — Where In

**Request**

```http
GET {BASE_URL}/api/report/where-in
```

**Controller method**

```php
SapReportController::testWhereIn()
```

**Expected result**

Returns records matching values supplied to WHERE IN.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-008 — Where Exists

**Request**

```http
GET {BASE_URL}/api/report/where-exists
```

**Controller method**

```php
SapReportController::testWhereExists()
```

**Expected result**

Returns records where the configured related/existence condition is true.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-009 — Where Not Exists

**Request**

```http
GET {BASE_URL}/api/report/where-not-exists
```

**Controller method**

```php
SapReportController::testWhereNotExists()
```

**Expected result**

Returns records where the configured related/existence condition is false.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-010 — Union

**Request**

```http
GET {BASE_URL}/api/report/union
```

**Controller method**

```php
SapReportController::testUnion()
```

**Expected result**

Returns the combined result of the configured UNION queries.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-011 — Distinct

**Request**

```http
GET {BASE_URL}/api/report/distinct
```

**Controller method**

```php
SapReportController::testDistinct()
```

**Expected result**

Returns unique/distinct results.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-012 — Select Raw

**Request**

```http
GET {BASE_URL}/api/report/select-raw
```

**Controller method**

```php
SapReportController::testSelectRaw()
```

**Expected result**

Returns results using a raw SELECT expression.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-013 — Order By Raw

**Request**

```http
GET {BASE_URL}/api/report/order-by-raw
```

**Controller method**

```php
SapReportController::testOrderByRaw()
```

**Expected result**

Returns results ordered using a raw ORDER BY expression.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-014 — Group By Raw

**Request**

```http
GET {BASE_URL}/api/report/group-by-raw
```

**Controller method**

```php
SapReportController::testGroupByRaw()
```

**Expected result**

Returns grouped results using a raw GROUP BY expression.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

## TC-REPORT-015 — Having Raw

**Request**

```http
GET {BASE_URL}/api/report/having-raw
```

**Controller method**

```php
SapReportController::testHavingRaw()
```

**Expected result**

Returns grouped results filtered using a raw HAVING expression.

**Test steps**

1. Open the endpoint.
2. Add the parameters required by the controller implementation, if any.
3. Send the request.
4. Check the returned records/aggregate/query result.
5. Compare the result with the expected database data.

**Result:** [ ] PASS  [ ] FAIL  
**HTTP Status:** __________  
**Actual Result / Notes:** __________________________________________

# C. General Validation

## HTTP Status

| Situation | Expected |
|---|---|
| Successful query | `200 OK` unless controller defines another response |
| Validation problem | Appropriate `4xx` response |
| Record not found | Verify controller behavior |
| Database/query exception | Appropriate error handling; investigate server log |

> The exact status codes and JSON response structure are not specified in the supplied route source. Confirm them from the controller implementation.

## Browser Test

For a simple endpoint:

```text
http://127.0.0.1:8000/api/sap/get
```

Replace the base URL and `/api` prefix according to your project.

## Postman Test

Create an environment:

```text
BASE_URL = http://127.0.0.1:8000
```

Then request:

```text
{{BASE_URL}}/api/sap/get
```

For each endpoint, record:

- Status code
- Response body
- Response time
- Database result correctness
- PASS/FAIL

## Final Sign-Off

| Area | Passed | Failed | Remarks |
|---|---:|---:|---|
| Basic Eloquent queries | ____ | ____ | |
| WHERE conditions | ____ | ____ | |
| Sorting / limit / offset | ____ | ____ | |
| Pagination | ____ | ____ | |
| Aggregate functions | ____ | ____ | |
| JOIN / report queries | ____ | ____ | |
| GROUP BY / HAVING | ____ | ____ | |
| Raw queries | ____ | ____ | |
| Chunk / cursor / lazy | ____ | ____ | |
| Error handling | ____ | ____ | |

**Overall Result:** [ ] PASS  [ ] FAIL

**Tester:** ____________________  
**Date:** ____________________  
**Approved By:** ____________________
