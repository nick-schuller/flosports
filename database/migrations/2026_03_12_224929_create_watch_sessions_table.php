<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('watch_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('sessionId')->unique(); // unique per session
            $table->string('userId');
            $table->string('eventId');
            $table->enum('status', ['active','paused','ended'])->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->float('current_position')->nullable();
            $table->string('current_quality')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('watch_sessions');
    }
};