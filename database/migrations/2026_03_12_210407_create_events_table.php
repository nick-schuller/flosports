<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            // @todo new migration to modify session_id and user_id into actual id fields once those tables are made outside of PoC            
            $table->string('session_id');
            $table->string('user_id');
            $table->string('event_type');
            $table->string('event_id');
            $table->timestamp('event_timestamp')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('events');
    }
};