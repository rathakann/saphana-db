<?php

namespace App\Database\Hana;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;

class HanaGrammar extends Grammar
{
    protected $operators = [
        '=',
        '<',
        '>',
        '<=',
        '>=',
        '<>',
        '!=',
        'like',
        'not like',
        'in',
        'not in',
        'is',
        'is not',
        'between',
        'not between',
    ];
    public function compileExists(Builder $query)
    {
        $originalColumns = $query->columns;

        $query->columns = [
            new \Illuminate\Database\Query\Expression('1 AS "exists"'),
        ];

        $sql = $this->compileSelect($query);

        $query->columns = $originalColumns;

        return $sql;
    }

    protected function compileAggregate(Builder $query, $aggregate)
    {
        $column = $this->columnize($aggregate['columns']);

        if (
            $query->distinct &&
            $column !== '*' &&
            ! str_contains($column, 'distinct ')
        ) {
            $column = 'distinct ' . $column;
        }

        return 'select ' .
            $aggregate['function'] .
            '(' . $column . ') as "aggregate"';
    }

    protected function compileLimit(Builder $query, $limit)
    {
        return 'limit ' . (int) $limit;
    }

    protected function compileOffset(Builder $query, $offset)
    {
        return 'offset ' . (int) $offset;
    }

    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }
}
