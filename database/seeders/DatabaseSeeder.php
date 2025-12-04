<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ===== User Default =====
        $user = User::create([
            'name' => 'sirL',
            'email' => 'sirL@gmail.com',
            'password' => Hash::make('password')
        ]);

        // ===== Buku Default =====
        $book = Book::create([
            'user_id' => $user->id,
            'name' => 'Buku Utama',
            'description' => 'Buku keuangan utama',
            'icon' => '📖',
            'color' => '#4CAF50',
            'is_default' => true,
        ]);

        // Wallet + Category + Transaction
        $wallets = $this->createDefaultWallets($book);
        $categories = $this->createDefaultCategories($book);
        $this->createDefaultTransactions($book, $wallets, $categories);

        // Info
        $this->command->info("Default data created successfully!");
        $this->command->info("Email: sirL@gmail.com");
        $this->command->info("Password: password123");
    }

    /**
     * Create default wallets
     */
    private function createDefaultWallets(Book $book): array
    {
        $wallets = [
            [
                'book_id' => $book->id,
                'name' => 'Tunai',
                'type' => 'CASH',
                'icon' => '💵',
                'color' => '#4CAF50',
                'initial_balance' => 0,
                'is_default' => true,
            ],
            [
                'book_id' => $book->id,
                'name' => 'Bank',
                'type' => 'BANK',
                'icon' => '🏦',
                'color' => '#2196F3',
                'initial_balance' => 0,
                'is_default' => false,
            ],
            [
                'book_id' => $book->id,
                'name' => 'E-Wallet',
                'type' => 'E_WALLET',
                'icon' => '📱',
                'color' => '#FF9800',
                'initial_balance' => 0,
                'is_default' => false,
            ]
        ];

        $created = [];

        foreach ($wallets as $data) {
            $created[] = Wallet::create($data);
        }

        return $created;
    }

    /**
     * Create default categories
     */
    private function createDefaultCategories(Book $book): array
    {
        $categories = [
            ['name' => 'Makanan & Minuman',  'icon' => '🍔', 'type' => 'PENGELUARAN'],
            ['name' => 'Transport',          'icon' => '🚌', 'type' => 'PENGELUARAN'],
            ['name' => 'Belanja',            'icon' => '🛒', 'type' => 'PENGELUARAN'],
            ['name' => 'Hiburan',            'icon' => '🎮', 'type' => 'PENGELUARAN'],
            ['name' => 'Kesehatan',          'icon' => '💊', 'type' => 'PENGELUARAN'],
            ['name' => 'Pendidikan',         'icon' => '📚', 'type' => 'PENGELUARAN'],
            ['name' => 'Tagihan',            'icon' => '💡', 'type' => 'PENGELUARAN'],
            ['name' => 'Rumah Tangga',       'icon' => '🏠', 'type' => 'PENGELUARAN'],
            ['name' => 'Olahraga',           'icon' => '⚽', 'type' => 'PENGELUARAN'],
            ['name' => 'Kecantikan',         'icon' => '💄', 'type' => 'PENGELUARAN'],

            ['name' => 'Gaji',               'icon' => '💼', 'type' => 'PEMASUKAN'],
            ['name' => 'Bonus',              'icon' => '💰', 'type' => 'PEMASUKAN'],
            ['name' => 'Investasi',          'icon' => '📈', 'type' => 'PEMASUKAN'],
            ['name' => 'Hadiah',             'icon' => '🎁', 'type' => 'PEMASUKAN'],
            ['name' => 'Freelance',          'icon' => '💻', 'type' => 'PEMASUKAN'],

            ['name' => 'Lainnya (Pemasukan)',  'icon' => '⚙️', 'type' => 'PEMASUKAN'],
            ['name' => 'Lainnya (Pengeluaran)', 'icon' => '⚙️', 'type' => 'PENGELUARAN'],
        ];

        $created = [];

        foreach ($categories as $cat) {
            $created[] = Category::create([
                'book_id' => $book->id,
                'name' => $cat['name'],
                'icon' => $cat['icon'],
                'type' => $cat['type'],
                'is_default' => true,
                'created_at_ts' => time(), // detik
            ]);
        }

        return $created;
    }

    /**
     * Create some example default transactions
     */
    private function createDefaultTransactions(Book $book, array $wallets, array $categories): void
    {
        if (empty($wallets) || empty($categories)) {
            return;
        }

        $wallet = $wallets[0]; // Wallet Tunai
        $makanan = collect($categories)->firstWhere('name', 'Makanan & Minuman');
        $gaji = collect($categories)->firstWhere('name', 'Gaji');

        $tx = [
            [
                'book_id' => $book->id,
                'wallet_id' => $wallet->id,
                'category_id' => $makanan->id,
                'type' => 'PENGELUARAN',
                'amount' => 25000,
                'note' => 'Sarapan pagi',
                'created_at_ms' => round(microtime(true) * 1000),
            ],
            [
                'book_id' => $book->id,
                'wallet_id' => $wallet->id,
                'category_id' => $gaji->id,
                'type' => 'PEMASUKAN',
                'amount' => 2000000,
                'note' => 'Gaji bulanan',
                'created_at_ms' => round(microtime(true) * 1000),
            ]
        ];

        foreach ($tx as $t) {
            Transaction::create($t);
        }
    }
}
