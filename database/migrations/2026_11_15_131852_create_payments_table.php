<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lease_id')->constrained('leases')->restrictOnDelete();
            $table->string('billing_period', 50);
            $table->unsignedBigInteger('amount');
            $table->date('due_date')->index();
            $table->string('payment_method')->nullable();
            $table->string('proof_photo_path')->nullable();
            $table->string('status')->default(PaymentStatus::Unpaid->value)->index();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['lease_id', 'billing_period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};