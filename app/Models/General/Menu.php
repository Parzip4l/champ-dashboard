<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'title', 'icon', 'url', 'parent_id', 'is_active', 'order'
    ];

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }
}
