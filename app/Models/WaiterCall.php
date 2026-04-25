<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaiterCall extends Model
{
    use HasFactory;

    public function getConnectionName()
    {
        return config('database.waiter_calls_connection', config('database.default'));
    }

    protected $fillable = [
        'table_no',
        'status',
        'note',
        'attended_at',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
    ];

    public function markAsAttended(): void
    {
        $this->update([
            'status' => 'attended',
            'attended_at' => now(),
        ]);
    }
}