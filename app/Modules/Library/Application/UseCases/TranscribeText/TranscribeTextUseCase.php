<?php

namespace App\Modules\Library\Application\UseCases\TranscribeText;

use Symfony\Component\Process\Process;

final readonly class TranscribeTextUseCase
{
    /**
     * @var array<string, string>
     */
    private const VOICES = [
        'en' => 'en',
        'ru' => 'ru',
        'de' => 'de',
        'fr' => 'fr',
        'es' => 'es',
        'it' => 'it',
    ];

    public function handle(TranscribeTextCommand $command): ?string
    {
        $text = trim($command->text);

        if ($text === '' || mb_strlen($text) > 200) {
            return null;
        }

        $voice = self::VOICES[$command->lang] ?? 'en';

        $ipa = new Process(['espeak-ng', '--ipa', '-v', $voice, $text]);
        $ipa->setTimeout(10);
        $ipa->run();

        if (! $ipa->isSuccessful()) {
            return null;
        }

        $line = trim(explode("\n", (string) $ipa->getOutput())[0] ?? '');

        return $line === '' ? null : $line;
    }
}
