<?php

namespace App\Domains\Auth\Infrastructure\Persistence\Database\Migrations;

use App\Modules\Auth\Domain\Enums\AuthProviderEnum;
use App\Modules\Auth\Domain\Enums\AuthPurposeEnum;
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
        Schema::create('auth_identities', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Identity ID');
            $table->uuid('user_id');

            $table->enum('provider', array_column(AuthProviderEnum::cases(), 'value'))->comment('Auth provider');
            $table->string('provider_id')->comment('Unique ID within provider');

            $table->string('secret')->nullable()->comment('Credential hash');

            $table->text('access_token')->nullable()->comment('External access token');
            $table->text('refresh_token')->nullable()->comment('External refresh token');
            $table->timestampTz('token_expires_at')->nullable()->index()->comment('External token expiry');
            $table->timestampTz('verified_at')->nullable()->comment('When identity was verified');
            $table->timestampTz('last_used_at')->nullable()->comment('Last login via, this identity');
            $table->timestampsTz();

            $table->unique(['provider', 'provider_id'], 'uq_provider_identity');

            $table->index([
                'user_id',
                'provider',
            ], 'idx_user_provider');

            $table->comment('All auth methods per user.');
        });

        Schema::create('auth_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Identity ID');
            $table->uuid('user_id');

            $table->string('country_code', 5)->comment('Dial code (+7, +1, ..)');
            $table->string('country_iso', 2)->comment('RU, US, ..');
            $table->string('national_number', 20)->comment('National part without country code');
            $table->string('e164_format', 25)->unique()->comment('Canonical, +79999999999');

            $table->boolean('is_primary')->default(false);
            $table->timestampTz('verified_at')->nullable();

            $table->timestampsTz();

            $table->index([
                'country_iso',
                'national_number',
            ], 'idx_contact_lookup');

            $table->comment('Contact details');
        });

        Schema::create('auth_otp_codes', function (Blueprint $table) {
            $table->uuid('uid')->primary()->comment('Identity ID');

            $table->string('provider_value', 25)->comment('Target provider value');
            $table->string('code_hash', 64)->comment('Code.');
            $table->enum('purpose', array_column(AuthPurposeEnum::cases(), 'value'))->comment('Register, Auth');

            $table->tinyInteger('attempts')->default(0)->comment('Wrong attempt counter');
            $table->tinyInteger('max_attempts')->default(3)->comment('Max wrong attempts before burn');

            $table->boolean('is_used')->default(false)->comment('Burned after success or max attempts');
            $table->timestampTz('expires_at')->comment('OTP lifetime');
            $table->timestampTz('used_at')->nullable();
            $table->timestampsTz();

            $table->index([
                'provider_value',
            ], 'idx_otp_lookup');

            $table->comment('One time passwords for auth');
        });

        Schema::create('auth_otp_rate_limits', function (Blueprint $table) {
            $table->uuid('uid')->primary()->comment('Identity ID');

            $table->string('value', 25)->unique()->comment('Target provider value');
            $table->string('ip_address', 45)->nullable()->comment('IPv4 or IPv6');

            $table->tinyInteger('send_count')->default(0);
            $table->timestampTz('windows_starts_at')->comment('Current rate limit windows start');
            $table->timestampTz('blocked_until')->nullable();

            $table->timestampsTz();

            $table->index([
                'ip_address',
            ], 'idx_rate_limit_ip');

            $table->comment('Rate limiting for OTP send');
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Token ID');
            $table->uuidMorphs('tokenable');
            $table->string('name')->comment('Token name/identifier');
            $table->string('token', 64)->unique()->comment('Hashed token value');
            $table->text('abilities')->nullable()->comment('Token permissions/abilities');
            $table->timestampTz('last_used_at')->nullable()->comment('Last usage timestamp');
            $table->timestampTz('expires_at')->nullable()->index()->comment('Expiration timestamp');
            $table->timestampsTz();

            $table->comment('Personal access tokens for API authentication');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('auth_otp_rate_limits');
        Schema::dropIfExists('auth_otp_codes');
        Schema::dropIfExists('auth_contacts');
        Schema::dropIfExists('auth_identities');
    }
};
