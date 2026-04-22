<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_no',
        'total_price',
        'order_note',
        'status',
        'items_json',
        'completed_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function getItemsAttribute()
    {
        return json_decode($this->items_json, true) ?? [];
    }

    public function setItemsAttribute($value)
    {
        $this->items_json = json_encode($value);
    }
}