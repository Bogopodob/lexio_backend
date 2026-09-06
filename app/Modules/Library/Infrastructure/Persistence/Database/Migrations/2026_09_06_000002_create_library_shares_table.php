<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_user_id');
            $table->uuid('friend_user_id');
            $table->uuid('category_id');
            $table->timestampsTz();

            $table->foreign('owner_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('friend_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();

            $table->unique(['owner_user_id', 'friend_user_id', 'category_id'], 'library_shares_unique');
            $table->index(['friend_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_shares');
    }
};
