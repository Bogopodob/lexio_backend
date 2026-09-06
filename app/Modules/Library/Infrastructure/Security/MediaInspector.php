<?php

namespace App\Modules\Library\Infrastructure\Security;

final class InvalidMediaException extends \InvalidArgumentException {}

/**
 * Structural verification of user-uploaded media.
 * Mirrors the avatar pipeline: magic bytes, real parse where
 * possible, strict allowlists, hard size caps.
 */
final class MediaInspector
{
    public const MAX_IMAGE_BYTES = 5 * 1024 * 1024;

    public const MAX_AUDIO_BYTES = 10 * 1024 * 1024;

    public const MAX_IMAGE_DIMENSION = 4096;

    /**
     * @return array{extension: string, mime: string}
     *
     * @throws InvalidMediaException
     */
    public static function inspectImage(string $path): array
    {
        self::assertSize($path, self::MAX_IMAGE_BYTES);

        $head = self::head($path, 8);

        $isPng = str_starts_with($head, "\x89PNG\r\n\x1a\n");
        $isJpeg = str_starts_with($head, "\xFF\xD8\xFF");

        if (! $isPng && ! $isJpeg) {
            throw new InvalidMediaException('NOT_IMAGE');
        }

        $info = @getimagesize($path);

        if ($info === false) {
            throw new InvalidMediaException('NOT_IMAGE');
        }

        $mime = $info['mime'] ?? '';

        if (($isPng && $mime !== 'image/png') || ($isJpeg && $mime !== 'image/jpeg')) {
            throw new InvalidMediaException('NOT_IMAGE');
        }

        if ($info[0] < 1 || $info[1] < 1 || $info[0] > self::MAX_IMAGE_DIMENSION || $info[1] > self::MAX_IMAGE_DIMENSION) {
            throw new InvalidMediaException('BAD_SIZE');
        }

        self::assertNoPngTrailingData($path, $isPng);

        return ['extension' => $isPng ? 'png' : 'jpg', 'mime' => $mime];
    }

    /**
     * @return array{extension: string, mime: string}
     *
     * @throws InvalidMediaException
     */
    public static function inspectAudio(string $path): array
    {
        self::assertSize($path, self::MAX_AUDIO_BYTES);

        $head = self::head($path, 12);

        if (str_starts_with($head, 'ID3') || (strlen($head) >= 2 && $head[0] === "\xFF" && (ord($head[1]) & 0xE0) === 0xE0)) {
            return ['extension' => 'mp3', 'mime' => 'audio/mpeg'];
        }

        if (str_starts_with($head, 'OggS')) {
            return ['extension' => 'ogg', 'mime' => 'audio/ogg'];
        }

        if (str_starts_with($head, "\x1A\x45\xDF\xA3")) {
            return ['extension' => 'webm', 'mime' => 'audio/webm'];
        }

        if (strlen($head) >= 12 && str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WAVE') {
            $tail = @file_get_contents($path, false, null, max(0, filesize($path) - 2), 2);

            if ($tail === false || $tail === '') {
                throw new InvalidMediaException('TRUNCATED');
            }

            return ['extension' => 'wav', 'mime' => 'audio/wav'];
        }

        if (strlen($head) >= 8 && substr($head, 4, 4) === 'ftyp') {
            return ['extension' => 'm4a', 'mime' => 'audio/mp4'];
        }

        throw new InvalidMediaException('NOT_AUDIO');
    }

    /**
     * @throws InvalidMediaException
     */
    private static function assertSize(string $path, int $max): void
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidMediaException('UNREADABLE');
        }

        $size = filesize($path);

        if ($size === false || $size <= 0) {
            throw new InvalidMediaException('EMPTY');
        }

        if ($size > $max) {
            throw new InvalidMediaException('TOO_BIG');
        }
    }

    /**
     * @throws InvalidMediaException
     */
    private static function head(string $path, int $bytes): string
    {
        $head = @file_get_contents($path, false, null, 0, $bytes);

        if (! is_string($head) || $head === '') {
            throw new InvalidMediaException('UNREADABLE');
        }

        return $head;
    }

    /**
     * @throws InvalidMediaException
     */
    private static function assertNoPngTrailingData(string $path, bool $isPng): void
    {
        if (! $isPng) {
            $size = filesize($path);
            $tail = @file_get_contents($path, false, null, max(0, (int) $size - 2), 2);

            if ($tail !== "\xFF\xD9") {
                throw new InvalidMediaException('TRUNCATED');
            }

            return;
        }

        $size = (int) filesize($path);
        $handle = @fopen($path, 'rb');

        if (! $handle) {
            throw new InvalidMediaException('UNREADABLE');
        }

        try {
            fseek($handle, 8);
            $type = '';

            while (! feof($handle)) {
                $header = fread($handle, 8);

                if (! is_string($header) || strlen($header) < 8) {
                    break;
                }

                $length = unpack('N', substr($header, 0, 4))[1];
                $type = substr($header, 4, 4);

                if ($length > $size) {
                    break;
                }

                fseek($handle, $length + 4, SEEK_CUR);

                if ($type === 'IEND') {
                    break;
                }
            }

            if ($type !== 'IEND' || ftell($handle) !== $size) {
                throw new InvalidMediaException('TRAILING_DATA');
            }
        } finally {
            fclose($handle);
        }
    }
}
