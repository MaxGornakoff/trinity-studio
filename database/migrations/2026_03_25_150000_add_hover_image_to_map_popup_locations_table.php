<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_popup_locations', function (Blueprint $table) {
            $table->string('hover_image')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('map_popup_locations', function (Blueprint $table) {
            $table->dropColumn('hover_image');
        });
    }
};
