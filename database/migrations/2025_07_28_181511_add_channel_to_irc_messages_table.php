<?php

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
        Schema::table('i_r_c_messages', function (Blueprint $table) {
            $table->string('channel')->default('#general')->after('message');
            $table->index('channel'); // Index para búsquedas por canal
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('i_r_c_messages', function (Blueprint $table) {
            $table->dropIndex(['channel']);
            $table->dropColumn('channel');
        });
    }
};
