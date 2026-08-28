<?php

namespace App\Database\Hana;

use DateTimeInterface;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\Grammar as QueryGrammar;
use Illuminate\Database\Query\Processors\Processor;
use PDO;
use PDOStatement;

class HanaConnection extends Connection
{
    /**
     * Get the default query grammar.
     */
    protected function getDefaultQueryGrammar()
    {
        return new HanaGrammar($this);
    }

    /**
     * Get the default post processor.
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor();
    }

    /**
     * Prepare bindings using Laravel's normal rules.
     */
    public function prepareBindings(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format(
                    $this->getQueryGrammar()->getDateFormat()
                );
            } elseif (is_bool($value)) {
                $bindings[$key] = (int) $value;
            }
        }

        return $bindings;
    }

    /**
     * Execute SELECT without PDO prepared statements.
     */
    public function select(
        $query,
        $bindings = [],
        $useReadPdo = true,
        array $fetchUsing = []
    ) {
        return $this->run(
            $query,
            $bindings,
            function ($query, $bindings) use ($useReadPdo, $fetchUsing) {
                if ($this->pretending()) {
                    return [];
                }

                $query = $this->compileWithBindings($query, $bindings);

                $statement = $this->getPdoForSelect($useReadPdo)
                    ->query($query);

                $statement->setFetchMode($this->getFetchMode());

                return $statement->fetchAll(...$fetchUsing);
            }
        );
    }

    /**
     * Execute SELECT and return multiple result sets.
     */
    public function selectResultSets(
        $query,
        $bindings = [],
        $useReadPdo = true,
        array $fetchUsing = []
    ) {
        return $this->run(
            $query,
            $bindings,
            function ($query, $bindings) use ($useReadPdo, $fetchUsing) {
                if ($this->pretending()) {
                    return [];
                }

                $query = $this->compileWithBindings($query, $bindings);

                $statement = $this->getPdoForSelect($useReadPdo)
                    ->query($query);

                $sets = [];

                do {
                    $sets[] = $statement->fetchAll(...$fetchUsing);
                } while ($statement->nextRowset());

                return $sets;
            }
        );
    }

    /**
     * Execute INSERT.
     */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Execute UPDATE.
     */
    public function update($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute DELETE.
     */
    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute a statement without prepared statements.
     */
    public function statement($query, $bindings = [])
    {
        return $this->run(
            $query,
            $bindings,
            function ($query, $bindings) {
                if ($this->pretending()) {
                    return true;
                }

                $query = $this->compileWithBindings($query, $bindings);

                $result = $this->getPdo()->exec($query);

                $this->recordsHaveBeenModified($result !== false);

                return $result !== false;
            }
        );
    }

    /**
     * Execute UPDATE / DELETE and return affected rows.
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run(
            $query,
            $bindings,
            function ($query, $bindings) {
                if ($this->pretending()) {
                    return 0;
                }

                $query = $this->compileWithBindings($query, $bindings);

                $statement = $this->getPdo()->query($query);

                $count = $statement->rowCount();

                $this->recordsHaveBeenModified($count > 0);

                return $count;
            }
        );
    }

    /**
     * Compile SQL by replacing ? placeholders with safely quoted values.
     *
     * IMPORTANT:
     * This is intentionally used because HDBODBC/PDO_ODBC prepared
     * statements fail in this environment.
     */
    protected function compileWithBindings(
        string $query,
        array $bindings
    ): string {
        if (empty($bindings)) {
            return $query;
        }

        $bindings = $this->prepareBindings($bindings);

        $result = '';
        $length = strlen($query);
        $bindingIndex = 0;

        $inSingleQuote = false;
        $inDoubleQuote = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $query[$i];

            /*
             * Handle SQL single-quoted strings.
             */
            if ($char === "'" && !$inDoubleQuote) {
                /*
                 * SQL escaped quote: ''
                 */
                if (
                    $inSingleQuote &&
                    $i + 1 < $length &&
                    $query[$i + 1] === "'"
                ) {
                    $result .= "''";
                    $i++;
                    continue;
                }

                $inSingleQuote = !$inSingleQuote;

                $result .= $char;

                continue;
            }

            /*
             * Handle quoted identifiers.
             */
            if ($char === '"' && !$inSingleQuote) {
                /*
                 * HANA escaped identifier quote: ""
                 */
                if (
                    $inDoubleQuote &&
                    $i + 1 < $length &&
                    $query[$i + 1] === '"'
                ) {
                    $result .= '""';
                    $i++;
                    continue;
                }

                $inDoubleQuote = !$inDoubleQuote;

                $result .= $char;

                continue;
            }

            /*
             * Replace ? only when we're outside SQL strings/identifiers.
             */
            if (
                $char === '?' &&
                !$inSingleQuote &&
                !$inDoubleQuote
            ) {
                if (!array_key_exists($bindingIndex, $bindings)) {
                    throw new \RuntimeException(
                        'HANA binding count does not match SQL placeholders.'
                    );
                }

                $result .= $this->quoteHanaValue(
                    $bindings[$bindingIndex]
                );

                $bindingIndex++;

                continue;
            }

            $result .= $char;
        }

        if ($bindingIndex !== count($bindings)) {
            throw new \RuntimeException(
                'HANA SQL contains fewer placeholders than bindings.'
            );
        }

        return $result;
    }

    /**
     * Safely convert a PHP value to a HANA SQL literal.
     */
    protected function quoteHanaValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new \InvalidArgumentException(
                    'Invalid floating-point binding.'
                );
            }

            return sprintf('%.15g', $value);
        }

        if ($value instanceof DateTimeInterface) {
            $value = $value->format(
                $this->getQueryGrammar()->getDateFormat()
            );
        }

        /*
         * String escaping for HANA SQL.
         *
         * Example:
         * O'Reilly
         * becomes
         * 'O''Reilly'
         */
        $value = (string) $value;

        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     * Return PDO fetch mode.
     */
    protected function getFetchMode(): int
    {
        return PDO::FETCH_OBJ;
    }
}
