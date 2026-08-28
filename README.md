# Laravel 13 + SAP HANA Eloquent Reporting
This project uses Laravel 13 Eloquent with SAP HANA through the `pdo_odbc` / `HDBODBC` driver.
The implementation is designed primarily for **read-only reporting**, not SAP transaction creation, updating, or deletion.
---
# 1. SAP HANA Database Configuration
In `config/database.php`:
```php
'saphana' => [
    'driver' => 'odbc',
    'dsn' => 'odbc:DRIVER={HDBODBC};SERVERNODE=' .
        env('HANA_HOST') . ':' .
        env('HANA_PORT', 30015) .
        ';DATABASE=' .
        env('HANA_DATABASE') .
        ';CHAR_AS_UTF8=TRUE',
    'host' => env('HANA_HOST'),
    'port' => env('HANA_PORT', 30015),
    'database' => env('HANA_DATABASE'),
    'username' => env('HANA_USERNAME'),
    'password' => env('HANA_PASSWORD'),
    'prefix' => '',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
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
    ->orderBy('CardCode')
    ->lazy()
    ->take(20)
    ->all();
