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
        Schema::create('i_r_c_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // ej: "Chat Hispano"
            $table->string('host');             // ej: irc.chathispano.com
            $table->unsignedInteger('port')->default(6667);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('i_r_c_servers');
    }
};
