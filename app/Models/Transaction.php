<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'wallet_id',
        'category_id',
        'type',
        'amount',
        'note',
        'created_at_ms',
        'image_url',
    ];

    protected $casts = [
        'amount' => 'integer',
        'created_at_ms' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'PEMASUKAN');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'PENGELUARAN');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at_ms', [$startDate, $endDate]);
    }
}
