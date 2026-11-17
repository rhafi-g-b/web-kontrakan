<?php

declare(strict_types=1);

use App\Enums\LeaseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedTinyInteger('due_day')->default(1);
            $table->string('status')->default(LeaseStatus::Active->value)->index();
            $table->timestamps();

            $table->index(['unit_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leases');
    }
};