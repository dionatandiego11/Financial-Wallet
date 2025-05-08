<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // User initiating or primarily affected
            $table->enum('type', ['deposit', 'transfer', 'reversal', 'received_transfer']); // Type of transaction
            $table->decimal('amount', 15, 2);
            $table->foreignId('related_user_id')->nullable()->constrained('users')->onDelete('set null'); // For transfers (sender/receiver)
            $table->foreignId('original_transaction_id')->nullable()->constrained('transactions')->onDelete('set null'); // For reversals
            $table->string('description')->nullable();
            $table->enum('status', ['completed', 'pending', 'failed', 'reversed'])->default('completed');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
