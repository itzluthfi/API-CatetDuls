<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'name',
        'type',
        'color',
        'icon',
        'is_default',
        'created_at_ts',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at_ts' => 'integer',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
