<?php

declare(strict_types=1);

use App\Enums\UnitStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('unit_number', 50)->unique();
            $table->unsignedBigInteger('monthly_price');
            $table->string('status')->default(UnitStatus::Available->value)->index();
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};