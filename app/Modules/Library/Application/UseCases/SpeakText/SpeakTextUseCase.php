<?php

namespace App\Modules\Library\Application\UseCases\SpeakText;

use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

final readonly class SpeakTextUseCase
{
    /**
     * espeak voice per language code. Robot-grade, but instant and offline.
     *
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

    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    public function handle(SpeakTextCommand $command): ?SpeakTextResult
    {
        $text = trim($command->text);

        if ($text === '' || mb_strlen($text) > 200) {
            return null;
        }

        $voice = self::VOICES[$command->lang] ?? 'en';
        $hash = sha1($voice.':'.mb_strtolower($text));
        // Per-user path: media rows (and stream rights) stay owned.
        $relative = "library/tts/{$command->userId}/{$hash}.wav";

        $media = $this->library->findMediaByPath($relative);

        if ($media === null) {
            if (! Storage::disk('local')->exists($relative)) {
                $tmp = tempnam(sys_get_temp_dir(), 'tts');

                if ($tmp === false) {
                    return null;
                }

                $speak = new Process(['espeak-ng', '-v', $voice, '-w', $tmp, $text]);
                $speak->setTimeout(20);
                $speak->run();

                if (! $speak->isSuccessful() || filesize($tmp) === 0) {
                    @unlink($tmp);

                    return null;
                }

                Storage::disk('local')->put($relative, file_get_contents($tmp));
                @unlink($tmp);
            }

            $media = $this->library->saveMedia(
                $command->userId,
                'audio',
                $relative,
                'audio/wav',
                (int) Storage::disk('local')->size($relative),
            );
        }

        return new SpeakTextResult($media, $this->transcription($voice, $text));
    }

    private function transcription(string $voice, string $text): ?string
    {
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
