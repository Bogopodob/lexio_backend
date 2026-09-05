<?php

namespace App\Modules\User\Infrastructure\Security;

final class InvalidImageException extends \InvalidArgumentException {}

/**
 * Structural verification of uploaded raster images.
 *
 * Layered on top of Laravel's File::image() validation: magic bytes,
 * a real getimagesize() parse, an allowlist of mime types and hard
 * dimension caps. Rejects scripts, SVGs and truncated garbage before
 * anything touches the disk.
 */
final class AvatarInspector
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    public const MAX_DIMENSION = 4096;

    private const PNG_MAGIC = "\x89PNG\r\n\x1a\n";

    private const JPEG_MAGIC = "\xFF\xD8\xFF";

    /**
     * @return array{extension: string, mime: string, width: int, height: int}
     *
     * @throws InvalidImageException
     */
    public static function inspect(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidImageException('Файл не читается.');
        }

        $size = filesize($path);

        if ($size === false || $size <= 0) {
            throw new InvalidImageException('Пустой файл.');
        }

        if ($size > self::MAX_BYTES) {
            throw new InvalidImageException('Изображение больше 5 МБ.');
        }

        $head = @file_get_contents($path, false, null, 0, 8);

        if (! is_string($head) || $head === '') {
            throw new InvalidImageException('Не получилось прочитать файл.');
        }

        $isPng = str_starts_with($head, self::PNG_MAGIC);
        $isJpeg = str_starts_with($head, self::JPEG_MAGIC);

        if (! $isPng && ! $isJpeg) {
            throw new InvalidImageException('Это не PNG и не JPEG.');
        }

        $info = @getimagesize($path);

        if ($info === false) {
            throw new InvalidImageException('Файл не открывается как картинка.');
        }

        $mime = $info['mime'] ?? '';

        if ($isPng && $mime !== 'image/png') {
            throw new InvalidImageException('PNG повреждён.');
        }

        if ($isJpeg && $mime !== 'image/jpeg') {
            throw new InvalidImageException('JPEG повреждён.');
        }

        [$width, $height] = [$info[0], $info[1]];

        if ($width < 1 || $height < 1 || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw new InvalidImageException('Странный размер картинки.');
        }

        // No payloads glued after the image end (classic polyglot):
        // PNG must end with IEND and nothing after it,
        // JPEG must end with the EOI marker.
        self::assertNoTrailingData($path, $size, $isPng);

        return [
            'extension' => $isPng ? 'png' : 'jpg',
            'mime' => $mime,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @throws InvalidImageException
     */
    private static function assertNoTrailingData(string $path, int $size, bool $isPng): void
    {
        if ($isPng) {
            $handle = @fopen($path, 'rb');

            if (! $handle) {
                throw new InvalidImageException('Не получилось прочитать файл.');
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

                    // Sanity cap: a single chunk bigger than the whole file is corrupt.
                    if ($length > $size) {
                        break;
                    }

                    fseek($handle, $length + 4, SEEK_CUR);

                    if ($type === 'IEND') {
                        break;
                    }
                }

                if ($type !== 'IEND' || ftell($handle) !== $size) {
                    throw new InvalidImageException('После картинки есть лишние данные.');
                }
            } finally {
                fclose($handle);
            }

            return;
        }

        $tail = @file_get_contents($path, false, null, max(0, $size - 2), 2);

        if ($tail !== "\xFF\xD9") {
            throw new InvalidImageException('JPEG оборван или с лишними данными.');
        }
    }
}
