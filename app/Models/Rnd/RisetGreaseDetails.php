<?php

namespace App\Models\Rnd;

use Illuminate\Database\Eloquent\Model;

class RisetGreaseDetails extends Model
{
    protected $table = 'rnd_details_rstgrease';

    protected $fillable = [
        'master_id',
        'trial_method',
        'trial_result',
        'issue',
        'improvement_ideas',
        'improvement_schedule',
        'competitor_comparison',
        'status',
        'created_by',
        'attachment'
    ];

    public function master()
    {
        return $this->belongsTo(RisetGreaseMaster::class, 'master_id');
    }
}
