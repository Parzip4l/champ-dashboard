<?php

namespace App\Models\Po;

use Illuminate\Database\Eloquent\Model;
use App\Models\General\Distributor;
use App\Models\User;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PurchaseOrder extends Model
{
    use LogsActivity;
    // Log
    protected static $logAttributes = ['*'];

    protected static $logName = 'purchase_order';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll() // hanya catat perubahan, bukan semua field
            ->useLogName('purchase_order')
            ->setDescriptionForEvent(fn(string $eventName) => "PO {$eventName}");
    }

    protected $fillable = [
        'po_number', 'distributor_id', 'po_date', 'due_date',
        'payment_method', 'top', 'notes',
        'subtotal', 'discount', 'tax', 'total', 'status','created_by'
    ];

    public function distributor()
    {
        return $this->belongsTo(Distributor::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->po_number)) {
                $prefix = 'PO-' . date('Ymd');
                $last = self::where('po_number', 'like', "$prefix%")->orderBy('po_number', 'desc')->first();

                if (!$last) {
                    $model->po_number = $prefix . '0001';
                } else {
                    $lastNumber = (int)substr($last->po_number, -4);
                    $model->po_number = $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
                }
            }
        });
    }

    
}
