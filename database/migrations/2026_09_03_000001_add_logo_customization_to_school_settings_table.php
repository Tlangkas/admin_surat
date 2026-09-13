<?php

declare(strict_types=1);

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
        Schema::table('school_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('logo_width')->default(75)->after('logo_path');
            $table->tinyInteger('logo_offset_x')->default(0)->after('logo_width');
            $table->tinyInteger('logo_offset_y')->default(0)->after('logo_offset_x');
            $table->string('logo_valign', 20)->default('middle')->after('logo_offset_y');

            $table->string('logo_kanan_path')->nullable()->after('logo_valign');
            $table->unsignedSmallInteger('logo_kanan_width')->default(75)->after('logo_kanan_path');
            $table->tinyInteger('logo_kanan_offset_x')->default(0)->after('logo_kanan_width');
            $table->tinyInteger('logo_kanan_offset_y')->default(0)->after('logo_kanan_offset_x');
            $table->string('logo_kanan_valign', 20)->default('middle')->after('logo_kanan_offset_y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn([
                'logo_width',
                'logo_offset_x',
                'logo_offset_y',
                'logo_valign',
                'logo_kanan_path',
                'logo_kanan_width',
                'logo_kanan_offset_x',
                'logo_kanan_offset_y',
                'logo_kanan_valign',
            ]);
        });
    }
};
