<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'photo_path',
        'sort_order',
        'is_available',
        'show_in_kitchen',
        'show_in_bar',
        'mssql_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'show_in_kitchen' => 'boolean',
        'show_in_bar' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }
        return asset('storage/' . $this->photo_path);
    }
}