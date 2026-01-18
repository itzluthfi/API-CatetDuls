<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Memo extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'title',
        'content',
        'tags',
        'date',
        'is_deleted',
        'created_at_ms',
        'updated_at_ms',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'date' => 'integer',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
