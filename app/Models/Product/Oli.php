<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Oli extends Model
{
    use HasFactory;
    protected $table = 'delivery_oli';
    protected $fillable = ['tanggal','pengirim','jenis_oli','jumlah','receive_status'];
}
