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

        return [
            'extension' => $isPng ? 'png' : 'jpg',
            'mime' => $mime,
            'width' => $width,
            'height' => $height,
        ];
    }
}
