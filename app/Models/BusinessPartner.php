<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessPartner extends HanaModel
{
    protected $table = 'OCRD';
    protected $primaryKey = 'DocEntry';
    public $incrementing = true;
    public function groupTable(): string
    {
        return env('HANA_DATABASE') . '.OCRG';
    }
}
