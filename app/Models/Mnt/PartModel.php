<?php

namespace App\Models\Mnt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartModel extends Model
{
    use HasFactory;
    protected $table = 'mnt_parts';
    protected $fillable = ['item_id', 'name', 'description','backup_stock'];

    public function item()
    {
        return $this->belongsTo(ItemModel::class, 'item_id');
    }

    public function checklist()
    {
        return $this->hasMany(ChecklistModel::class, 'part_id');
    }
}
