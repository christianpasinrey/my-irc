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
        Schema::create('i_r_c_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained('i_r_c_servers')->onDelete('cascade');
            $table->string('nickname'); // Nickname of the user who sent the message
            $table->text('message');    // The actual message content
            $table->timestamp('timestamp')->useCurrent(); // Timestamp of when the message was sent
            $table->index(['server_id', 'timestamp']); // Index for faster querying by server
            $table->index('nickname'); // Index for faster querying by nickname
            $table->index('message');  // Index for faster searching in messages
            $table->unique(['server_id', 'timestamp', 'nickname', 'message'], 'unique_message'); // Unique constraint to prevent duplicate messages
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('i_r_c_messages');
    }
};
