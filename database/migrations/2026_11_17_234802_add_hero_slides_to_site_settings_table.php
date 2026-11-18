<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('hero_slide_1_path')->nullable()->after('hero_subtitle');
            $table->string('hero_slide_1_label')->default('Tampak Depan & Lingkungan')->after('hero_slide_1_path');
            $table->string('hero_slide_2_path')->nullable()->after('hero_slide_1_label');
            $table->string('hero_slide_2_label')->default('Area Parkir & Teras Unit')->after('hero_slide_2_path');
            $table->string('hero_slide_3_path')->nullable()->after('hero_slide_2_label');
            $table->string('hero_slide_3_label')->default('Bagian Dalam Ruangan')->after('hero_slide_3_path');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'hero_slide_1_path',
                'hero_slide_1_label',
                'hero_slide_2_path',
                'hero_slide_2_label',
                'hero_slide_3_path',
                'hero_slide_3_label',
            ]);
        });
    }
};