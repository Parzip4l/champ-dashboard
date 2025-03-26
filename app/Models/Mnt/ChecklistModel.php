<?php

namespace App\Models\Mnt;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChecklistModel extends Model
{
    use HasFactory;
    protected $table = 'mnt_checklists';

    protected $fillable = ['part_id', 'checklist_item','keterangan'];

    public function part()
    {
        return $this->belongsTo(PartModel::class, 'part_id');
    }

    public function item()
    {
        return $this->hasOneThrough(ItemModel::class, PartModel::class, 'id', 'id', 'part_id', 'item_id');
    }
}
