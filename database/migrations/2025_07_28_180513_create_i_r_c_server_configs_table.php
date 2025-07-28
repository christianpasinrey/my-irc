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
        Schema::create('i_r_c_server_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('i_r_c_servers')->onDelete('cascade');
            $table->string('key');   // Configuration key (e.g., "nickname", "channel")
            $table->string('value'); // Configuration value (e.g., "MyNickname", "#mychannel")
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('i_r_c_server_configs');
    }
};
