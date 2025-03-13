<?php

namespace App\Models\Mnt;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemModel extends Model
{
    use HasFactory;
    protected $table = 'mnt_item';
    protected $fillable = ['name', 'description', 'qr_code', 'qr_code_path'];

    public function parts()
    {
        return $this->hasMany(PartModel::class, 'item_id');
    }

    public function maintenances()
    {
        return $this->hasMany(ScheduleModel::class, 'item_id');
    }
}
