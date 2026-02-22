<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('payment_type', ['membership', 'fine', 'reservation', 'ebook']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'card', 'online'])->default('online');
            $table->enum('status', ['pending', 'paid', 'failed'])->default('paid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['due_reminder', 'overdue_alert', 'general']);
            $table->text('message');
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('payments');
    }
};
