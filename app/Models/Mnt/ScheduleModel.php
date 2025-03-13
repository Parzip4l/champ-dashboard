<?php

namespace App\Models\Mnt;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ScheduleModel extends Model
{
    use HasFactory;
    protected $table = 'mnt_schedule';
    protected $fillable = ['item_id', 'schedule', 'next_maintenance'];

    public function item()
    {
        return $this->belongsTo(ItemModel::class);
    }

    public function logs()
    {
        return $this->hasMany(LogModel::class);
    }
}
