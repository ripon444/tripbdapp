<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->string('withdrawal_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['bkash', 'nagad', 'rocket', 'bank_transfer'])->default('bkash');
            $table->string('account_number', 100);
            $table->string('bank_name', 100)->nullable();
            $table->string('branch_name', 100)->nullable();
            $table->string('routing_number', 50)->nullable();
            $table->enum('status', ['pending', 'approved', 'processing', 'completed', 'rejected', 'cancelled'])->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('driver_id');
            $table->index('wallet_id');
            $table->index('status');
            $table->index('withdrawal_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
