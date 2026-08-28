<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class HanaModel extends Model
{
    protected $connection = 'saphana';

    public $timestamps = false;

    /**
     * Get the fully-qualified SAP HANA table name.
     *
     * Example:
     * OCRD → TNLOIL_V051_01.OCRD
     * OCRG → TNLOIL_V051_01.OCRG
     */
    public static function hanaTable(string $table): string
    {
        return env('HANA_DATABASE') . '.' . $table;
    }

    /**
     * Get the model's fully-qualified table name.
     */
    public function getTable()
    {
        return static::hanaTable($this->table);
    }
}
