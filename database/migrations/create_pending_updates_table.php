<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pending_updates', function (Blueprint $table) {
            $table->id();
            $table->morphs('parent');
            $table->json('values');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('revert_at')->nullable();
            $table->timestamps();
        });
    }
};
