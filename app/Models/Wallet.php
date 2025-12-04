<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'name',
        'type',
        'icon',
        'color',
        'initial_balance',
        'is_default',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    protected $appends = ['current_balance'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationships
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Accessor untuk saldo saat ini
    public function getCurrentBalanceAttribute()
    {
        $income = $this->transactions()
            ->where('type', 'PEMASUKAN')
            ->sum('amount');

        $expense = $this->transactions()
            ->where('type', 'PENGELUARAN')
            ->sum('amount');

        return $this->initial_balance + $income - $expense;
    }
}
