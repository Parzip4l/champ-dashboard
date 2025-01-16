<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Model;

class RisetGreaseMaster extends Model
{
    protected $table = 'rnd_master_rstgrease';

    protected $fillable = [
        'batch_code',
        'product_name',
        'expected_start_date',
        'expected_end_date',
        'created_by',
    ];

    public function details()
    {
        return $this->hasMany(RisetGreaseDetails::class, 'master_id');
    }
}
