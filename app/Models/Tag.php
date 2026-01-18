<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
        'is_deleted',
        'created_at_ms',
        'updated_at_ms',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'color' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
