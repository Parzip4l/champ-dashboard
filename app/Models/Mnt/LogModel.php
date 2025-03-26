<?php

namespace App\Models\Mnt;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class LogModel extends Model
{
    use HasFactory;
    protected $table = 'mnt_logs';

    protected $fillable = [
        'maintenance_id',
        'item_id',
        'part_id',
        'performed_at',
        'status',
        'maintenance_by',
        'notes',
        'checklist_item'
    ];

    public function maintenance()
    {
        return $this->belongsTo(ScheduleModel::class, 'maintenance_id');
    }

    public function item()
    {
        return $this->belongsTo(ItemModel::class, 'item_id');
    }

    public function part()
    {
        return $this->belongsTo(PartModel::class, 'part_id');
    }

    public function checklists()
    {
        return $this->hasMany(MaintenanceChecklist::class, 'log_id');
    }

    public function maintenanceBy()
    {
        return $this->belongsTo(User::class, 'maintenance_by'); // Sesuaikan kolom foreign key
    }
}
