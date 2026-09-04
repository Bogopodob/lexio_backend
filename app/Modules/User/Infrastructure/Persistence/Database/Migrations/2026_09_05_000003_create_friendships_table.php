<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('addressee_id')->constrained('users')->cascadeOnDelete();

            $table->string('status', 10)->default('pending')->comment('pending, accepted, declined');

            $table->timestampsTz();

            $table->unique(['requester_id', 'addressee_id']);
            $table->index(['addressee_id', 'status']);
            $table->index(['requester_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
