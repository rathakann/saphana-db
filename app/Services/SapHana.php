<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class SapHana
{
    protected ?PDO $pdo = null;

    protected array $config;

    protected int $timeout;

    public function __construct()
    {
        $this->config = config('database.connections.saphana', []);

        $this->timeout = (int) ($this->config['timeout'] ?? 30);
    }

    /**
     * Get or create the PDO connection.
     */
    protected function connection(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $host = $this->config['host'] ?? env('HANA_HOST');
        $port = $this->config['port'] ?? env('HANA_PORT', 30015);
        $database = $this->config['database'] ?? env('HANA_DATABASE');

        $username = $this->config['username'] ?? env('HANA_USERNAME');
        $password = $this->config['password'] ?? env('HANA_PASSWORD');

        if (!$host) {
            throw new RuntimeException('HANA_HOST is not configured.');
        }

        if (!$database) {
            throw new RuntimeException('HANA_DATABASE is not configured.');
        }

        if (!$username) {
            throw new RuntimeException('HANA_USERNAME is not configured.');
        }

        /*
         * Important:
         *
         * We intentionally use PDO::query() instead of
         * PDO::prepare()->execute().
         *
         * Your HDBODBC + PDO_ODBC environment has already
         * been tested and confirmed to work with query().
         */
        $dsn = sprintf(
            'odbc:DRIVER={HDBODBC};SERVERNODE=%s:%s;DATABASE=%s',
            $host,
            $port,
            $database
        );

        try {
            $this->pdo = new PDO(
                $dsn,
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_TIMEOUT => $this->timeout,
                ]
            );

            Log::info('SAP HANA connection established.', [
                'host' => $host,
                'port' => $port,
                'database' => $database,
            ]);

            return $this->pdo;
        } catch (Throwable $e) {
            Log::error('SAP HANA connection failed.', [
                'host' => $host,
                'port' => $port,
                'database' => $database,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Close the current PDO connection.
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * Check whether a PDO connection currently exists.
     */
    public function connected(): bool
    {
        return $this->pdo instanceof PDO;
    }

    /**
     * Execute SELECT and return all rows.
     *
     * Example:
     *
     * $rows = $hana->select(
     *     'SELECT * FROM "OCRD" WHERE "CardCode" = :cardCode',
     *     ['cardCode' => 'V100091']
     * );
     */
    public function select(
        string $sql,
        array $bindings = []
    ): array {
        $start = microtime(true);

        $originalSql = $sql;

        try {
            $sql = $this->bindParameters($sql, $bindings);

            $this->logQuery($originalSql, $bindings);

            $stmt = $this->connection()->query($sql);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->logQueryCompleted(
                $originalSql,
                $start,
                count($rows)
            );

            return $rows;
        } catch (Throwable $e) {
            $this->logQueryFailed(
                $originalSql,
                $bindings,
                $start,
                $e
            );

            throw $e;
        }
    }

    /**
     * Execute SELECT and return first row.
     */
    public function selectOne(
        string $sql,
        array $bindings = []
    ): ?array {
        $rows = $this->select($sql, $bindings);

        return $rows[0] ?? null;
    }

    /**
     * Execute SELECT and return first column of first row.
     */
    public function scalar(
        string $sql,
        array $bindings = []
    ): mixed {
        $row = $this->selectOne($sql, $bindings);

        if ($row === null) {
            return null;
        }

        return array_values($row)[0] ?? null;
    }

    /**
     * Execute a statement that doesn't return rows.
     *
     * Useful for UPDATE / DELETE / INSERT.
     */
    public function statement(
        string $sql,
        array $bindings = []
    ): bool {
        $start = microtime(true);

        try {
            $sql = $this->bindParameters($sql, $bindings);

            $this->logQuery($sql, []);

            $this->connection()->query($sql);

            $this->logQueryCompleted($sql, $start);

            return true;
        } catch (Throwable $e) {
            $this->logQueryFailed(
                $sql,
                $bindings,
                $start,
                $e
            );

            throw $e;
        }
    }

    /**
     * Execute SELECT with pagination.
     *
     * Example:
     *
     * $result = $hana->paginate(
     *     'SELECT "CardCode", "CardName"
     *      FROM "TNLOIL_V051_01"."OCRD"
     *      WHERE "CardName" LIKE :keyword',
     *     ['keyword' => '%GOLD%'],
     *     page: 2,
     *     perPage: 20
     * );
     */
    public function paginate(
        string $sql,
        array $bindings = [],
        int $page = 1,
        int $perPage = 20
    ): array {
        $page = max(1, $page);

        $maxPerPage = (int) ($this->config['max_per_page'] ?? 1000);

        $perPage = max(
            1,
            min($perPage, $maxPerPage)
        );

        /*
     * Remove trailing semicolon because we append
     * LIMIT / OFFSET below.
     */
        $baseSql = rtrim(
            $sql,
            " \t\n\r;"
        );

        /*
     * Count total records.
     *
     * We wrap the original query as a subquery so that
     * ORDER BY, JOIN, WHERE, GROUP BY, etc. can be preserved.
     */
        $countSql = sprintf(
            'SELECT COUNT(*) AS "total" FROM (%s) AS "pagination_count"',
            $baseSql
        );

        $total = (int) $this->scalar(
            $countSql,
            $bindings
        );

        /*
     * Calculate pagination.
     */
        $lastPage = max(
            1,
            (int) ceil($total / $perPage)
        );

        /*
     * If requested page is beyond the last page,
     * return an empty data set instead of generating
     * an unnecessary large OFFSET.
     */
        if ($page > $lastPage && $total > 0) {
            return [
                'data' => [],
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => null,
                'to' => null,
                'has_more' => false,
                'has_previous' => true,
            ];
        }

        /*
     * HANA pagination:
     *
     * LIMIT <perPage> OFFSET <offset>
     */
        $offset = ($page - 1) * $perPage;

        $paginatedSql = sprintf(
            '%s LIMIT %d OFFSET %d',
            $baseSql,
            $perPage,
            $offset
        );

        $data = $this->select(
            $paginatedSql,
            $bindings
        );

        $count = count($data);

        /*
     * Calculate item positions.
     */
        $from = $count > 0
            ? $offset + 1
            : null;

        $to = $count > 0
            ? $offset + $count
            : null;

        return [
            'data' => $data,

            'current_page' => $page,

            'per_page' => $perPage,

            'total' => $total,

            'last_page' => $lastPage,

            'from' => $from,

            'to' => $to,

            'has_more' => $page < $lastPage,

            'has_previous' => $page > 1,
        ];
    }

    /**
     * Execute a callback inside a transaction.
     *
     * Example:
     *
     * $hana->transaction(function (SapHana $hana) {
     *     $hana->statement(...);
     *     $hana->statement(...);
     * });
     */
    public function transaction(callable $callback): mixed
    {
        $pdo = $this->connection();

        if ($pdo->inTransaction()) {
            return $callback($this);
        }

        $pdo->beginTransaction();

        try {
            $result = $callback($this);

            $pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            Log::error('SAP HANA transaction rolled back.', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Manually begin a transaction.
     */
    public function beginTransaction(): void
    {
        $this->connection()->beginTransaction();
    }

    /**
     * Commit current transaction.
     */
    public function commit(): void
    {
        $this->connection()->commit();
    }

    /**
     * Roll back current transaction.
     */
    public function rollBack(): void
    {
        if ($this->connection()->inTransaction()) {
            $this->connection()->rollBack();
        }
    }

    /**
     * Check whether currently inside a transaction.
     */
    public function inTransaction(): bool
    {
        return $this->connection()->inTransaction();
    }

    /**
     * Get the underlying PDO connection.
     */
    public function pdo(): PDO
    {
        return $this->connection();
    }

    /**
     * Convert named parameters to safe SQL literals.
     *
     * We deliberately don't use PDO prepared statements because
     * PDO_ODBC + HDBODBC is failing during SQLExecute() in this
     * environment.
     */
    protected function bindParameters(
        string $sql,
        array $bindings
    ): string {
        if (!$bindings) {
            return $sql;
        }

        /*
         * We process the SQL character-by-character so that
         * parameters inside quoted strings are not replaced.
         *
         * Example:
         *
         * WHERE "CardCode" = :cardCode
         *
         * becomes:
         *
         * WHERE "CardCode" = 'V100091'
         */
        $result = '';

        $length = strlen($sql);
        $i = 0;

        $inSingleQuote = false;
        $inDoubleQuote = false;

        while ($i < $length) {
            $char = $sql[$i];

            /*
             * Single quoted SQL string.
             */
            if ($char === "'" && !$inDoubleQuote) {
                $result .= $char;

                /*
                 * SQL escaped quote:
                 *
                 * ''
                 */
                if (
                    $inSingleQuote &&
                    $i + 1 < $length &&
                    $sql[$i + 1] === "'"
                ) {
                    $result .= "'";
                    $i += 2;
                    continue;
                }

                $inSingleQuote = !$inSingleQuote;

                $i++;
                continue;
            }

            /*
             * Double quoted identifier.
             */
            if ($char === '"' && !$inSingleQuote) {
                $result .= $char;

                /*
                 * HANA escaped identifier quote:
                 *
                 * ""
                 */
                if (
                    $inDoubleQuote &&
                    $i + 1 < $length &&
                    $sql[$i + 1] === '"'
                ) {
                    $result .= '"';
                    $i += 2;
                    continue;
                }

                $inDoubleQuote = !$inDoubleQuote;

                $i++;
                continue;
            }

            /*
             * Named parameter.
             */
            if (
                $char === ':' &&
                !$inSingleQuote &&
                !$inDoubleQuote
            ) {
                $j = $i + 1;

                while (
                    $j < $length &&
                    preg_match('/[A-Za-z0-9_]/', $sql[$j])
                ) {
                    $j++;
                }

                $name = substr(
                    $sql,
                    $i + 1,
                    $j - ($i + 1)
                );

                if ($name !== '') {
                    if (!array_key_exists($name, $bindings)) {
                        throw new RuntimeException(
                            "Missing SQL binding: :{$name}"
                        );
                    }

                    $result .= $this->quoteValue(
                        $bindings[$name]
                    );

                    $i = $j;

                    continue;
                }
            }

            $result .= $char;

            $i++;
        }

        return $result;
    }

    /**
     * Convert PHP value into a safe SQL literal.
     */
    protected function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof DateTimeInterface) {
            return $this->quoteString(
                $value->format('Y-m-d H:i:s')
            );
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new RuntimeException(
                    'Invalid floating-point SQL parameter.'
                );
            }

            return sprintf(
                '%.15g',
                $value
            );
        }

        if (is_string($value)) {
            return $this->quoteString($value);
        }

        throw new RuntimeException(
            'Unsupported SQL binding type: ' . get_debug_type($value)
        );
    }

    /**
     * Safely quote a string for SAP HANA SQL.
     *
     * SQL:
     *
     * ABC'123
     *
     * becomes:
     *
     * 'ABC''123'
     */
    protected function quoteString(string $value): string
    {
        return "'" . str_replace(
            "'",
            "''",
            $value
        ) . "'";
    }

    /**
     * Query logging.
     */
    protected function logQuery(
        string $sql,
        array $bindings
    ): void {
        if (!config('database.connections.saphana.log_queries', false)) {
            return;
        }

        Log::channel(
            config(
                'database.connections.saphana.log_channel',
                config('logging.default')
            )
        )->debug('SAP HANA query', [
            'sql' => $sql,
            'bindings' => $this->sanitizeBindings($bindings),
        ]);
    }

    /**
     * Successful query logging.
     */
    protected function logQueryCompleted(
        string $sql,
        float $start,
        ?int $rows = null
    ): void {
        if (!config('database.connections.saphana.log_queries', false)) {
            return;
        }

        $duration = round(
            (microtime(true) - $start) * 1000,
            2
        );

        Log::debug('SAP HANA query completed.', [
            'duration_ms' => $duration,
            'rows' => $rows,
        ]);
    }

    /**
     * Failed query logging.
     */
    protected function logQueryFailed(
        string $sql,
        array $bindings,
        float $start,
        Throwable $e
    ): void {
        Log::error('SAP HANA query failed.', [
            'sql' => $sql,
            'bindings' => $this->sanitizeBindings($bindings),
            'duration_ms' => round(
                (microtime(true) - $start) * 1000,
                2
            ),
            'exception' => get_class($e),
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Prevent sensitive values from being written to logs.
     */
    protected function sanitizeBindings(
        array $bindings
    ): array {
        $sensitive = [
            'password',
            'passwd',
            'token',
            'secret',
            'api_key',
            'apikey',
        ];

        foreach ($bindings as $key => $value) {
            if (
                in_array(
                    strtolower((string) $key),
                    $sensitive,
                    true
                )
            ) {
                $bindings[$key] = '[REDACTED]';
            }
        }

        return $bindings;
    }
}
