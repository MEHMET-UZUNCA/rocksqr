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
        'bar_status',
        'kitchen_status',
        'items_json',
        'bar_approved_at',
        'kitchen_started_at',
        'kitchen_ready_at',
        'completed_at',
        'symphony_processed_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'bar_approved_at' => 'datetime',
        'kitchen_started_at' => 'datetime',
        'kitchen_ready_at' => 'datetime',
        'completed_at' => 'datetime',
        'symphony_processed_at' => 'datetime',
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