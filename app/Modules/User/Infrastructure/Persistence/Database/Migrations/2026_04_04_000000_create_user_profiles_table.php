<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_profiles', function (Blueprint $table): void {
            $table->string('user_id')->primary();
            $table->string('name')->nullable();
            $table->string('lastname')->nullable();
            $table->string('surname')->nullable();
            $table->string('avatar')->nullable();
            $table->string('city')->nullable();
            $table->date('birth_date')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('is_doctor')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
        Schema::dropIfExists('users');
    }
};
