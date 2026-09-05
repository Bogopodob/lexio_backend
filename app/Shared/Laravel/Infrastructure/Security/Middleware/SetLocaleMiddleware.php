<?php

namespace App\Shared\Laravel\Infrastructure\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Picks the response language from Accept-Language (ru/en),
 * everything else falls back to the app default.
 */
final readonly class SetLocaleMiddleware
{
    private const SUPPORTED = ['ru', 'en'];

    public function handle(Request $request, Closure $next)
    {
        // Parsed manually: Symfony's getPreferredLanguage falls back to
        // the first supported language when nothing matches, but unknown
        // languages must stay on the app default (English).
        $locale = 'en';
        $header = (string) $request->headers->get('Accept-Language', '');

        if ($header !== '' && preg_match_all('/([a-zA-Z]{2,8})(?:-[a-zA-Z]{2,8})?(?:\s*;\s*q\s*=\s*([0-9.]+))?/', $header, $matches)) {
            $ranked = [];

            foreach ($matches[1] as $i => $lang) {
                $code = strtolower(substr($lang, 0, 2));
                $quality = isset($matches[2][$i]) && $matches[2][$i] !== '' ? (float) $matches[2][$i] : 1.0;

                if (in_array($code, self::SUPPORTED, true) && ! isset($ranked[$code])) {
                    $ranked[$code] = $quality;
                }
            }

            if ($ranked !== []) {
                arsort($ranked);
                $locale = (string) array_key_first($ranked);
            }
        }

        App::setLocale($locale);

        return $next($request);
    }
}
