<?php

namespace App\Modules\Auth\Infrastructure\Http\Takes;

use App\Modules\Auth\Infrastructure\Http\Resources\AuthInitResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class GetAuthInitTake
{
    public function handle(Request $request): JsonResponse
    {
        $policiesConfig = config('auth_countries.policies', []);
        if (! is_array($policiesConfig)) {
            $policiesConfig = [];
        }

        $language = $this->resolveLanguage($request);
        $countryIso = $this->resolveCountryIso($language, $policiesConfig);
        $allPolicies = $this->buildPolicies($policiesConfig);

        return AuthInitResource::make([
            'language' => $language,
            'country_iso' => $countryIso,
            'policies' => $allPolicies,
        ])->response();
    }

    private function resolveLanguage(Request $request): ?string
    {
        $rawLanguage = $request->header('Accept-Language', app()->getLocale());

        if (! is_string($rawLanguage) || trim($rawLanguage) === '') {
            return null;
        }

        $language = mb_strtolower(trim(explode(',', $rawLanguage)[0]));

        return $language !== '' ? $language : null;
    }

    /**
     * @param  array<string, mixed>  $policiesConfig
     */
    private function resolveCountryIso(?string $language, array $policiesConfig): ?string
    {
        if ($language === null) {
            return null;
        }

        $parts = preg_split('/[-_]/', $language) ?: [];
        $candidate = null;

        if (isset($parts[1]) && mb_strlen($parts[1]) === 2) {
            $candidate = mb_strtoupper($parts[1]);
        } elseif (isset($parts[0]) && mb_strlen($parts[0]) === 2) {
            $candidate = mb_strtoupper($parts[0]);
        }

        if ($candidate === null) {
            return null;
        }

        return array_key_exists($candidate, $policiesConfig) ? $candidate : null;
    }

    /** @param array<string, mixed> $policiesConfig */
    private function buildPolicies(array $policiesConfig): array
    {
        $result = [];

        foreach (array_keys($policiesConfig) as $countryIso) {
            if (! is_string($countryIso)) {
                continue;
            }

            $normalizedIso = mb_strtoupper(trim($countryIso));
            if ($normalizedIso === '') {
                continue;
            }

            $result[$normalizedIso] = [
                'country_iso' => $normalizedIso,
            ];
        }

        return $result;
    }
}
