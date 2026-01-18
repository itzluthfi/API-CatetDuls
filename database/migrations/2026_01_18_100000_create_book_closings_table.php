<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('book_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->bigInteger('period_start'); // Milliseconds
            $table->bigInteger('period_end');   // Milliseconds
            $table->string('period_label');
            $table->bigInteger('closed_at');    // Milliseconds
            $table->double('final_balance');
            $table->boolean('is_verified')->default(false);
            $table->text('notes')->nullable();
            
            // Sync fields
            $table->boolean('is_deleted')->default(false);
            $table->bigInteger('created_at_ms')->nullable(); // Android creation time
            $table->bigInteger('updated_at_ms')->nullable(); // Android update time
            $table->timestamps(); // Server timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_closings');
    }
};
