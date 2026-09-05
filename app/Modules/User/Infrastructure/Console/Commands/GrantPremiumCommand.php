<?php

namespace App\Modules\User\Infrastructure\Console\Commands;

use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Console\Command;

class GrantPremiumCommand extends Command
{
    protected $signature = 'user:grant-premium
        {email : user email}
        {--revoke : revoke premium instead of granting}';

    protected $description = 'Grant or revoke premium subscription (stub until real payments land)';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $revoke = (bool) $this->option('revoke');

        $updated = User::query()->where('email', $email)->update(['is_premium' => ! $revoke]);

        if ($updated === 0) {
            $this->error("User not found: {$email}");

            return self::FAILURE;
        }

        $this->info($revoke ? "Premium revoked: {$email}" : "Premium granted: {$email}");

        return self::SUCCESS;
    }
}
