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
        Schema::create('memos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->text('tags')->nullable(); // stored as comma separated string or JSON? Android sends string.
            $table->bigInteger('date'); // Milliseconds
            
            // Sync fields
            $table->boolean('is_deleted')->default(false);
            $table->bigInteger('created_at_ms')->nullable();
            $table->bigInteger('updated_at_ms')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memos');
    }
};
