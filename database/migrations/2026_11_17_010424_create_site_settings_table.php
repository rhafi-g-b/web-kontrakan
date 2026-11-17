<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('site_name')->default('Kontrakan Ibu');
            $table->string('site_tagline')->default('Hunian Sewa Bulanan Terpadu');
            $table->string('logo_path')->nullable();
            $table->string('hero_title')->default('Hunian sewa bulanan yang tenang, bersih, dan terawat.');
            $table->text('hero_subtitle')->nullable();
            $table->string('whatsapp_number')->default('081312132075');
            $table->text('footer_description')->nullable();
            $table->string('survey_hours')->default('Setiap Hari: 08.00 – 20.00 WIB');
            $table->string('address')->nullable();
            $table->text('survey_note')->nullable();
            $table->string('copyright_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};