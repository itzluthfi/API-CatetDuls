<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'period_start',
        'period_end',
        'period_label',
        'closed_at',
        'final_balance',
        'is_verified',
        'notes',
        'is_deleted',
        'created_at_ms',
        'updated_at_ms',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_deleted' => 'boolean',
        'final_balance' => 'double',
        'period_start' => 'integer',
        'period_end' => 'integer',
        'closed_at' => 'integer',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
