<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    protected $fillable = ['name', 'code', 'description', 'address'];

    public function stocks()
    {
        return $this->hasMany(WarehouseStock::class);
    }
}