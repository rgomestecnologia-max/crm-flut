<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Centraliza o acesso ao disco de mídia (local ou R2/S3).
 *
 * Em dev: MEDIA_DISK=public (disco local, /storage/app/public)
 * Em prod: MEDIA_DISK=r2 (Cloudflare R2 via driver S3)
 *
 * Vantagem: trocar o storage é só mudar o .env, sem mexer em código.
 */
class MediaStorage
{
    /**
     * Nome do disco configurado.
     */
    public static function diskName(): string
    {
        return config('filesystems.media', 'public');
    }

    /**
     * Instância do disco.
     */
    public static function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk(static::diskName());
    }

    /**
     * Salva conteúdo e retorna o path relativo.
     */
    public static function put(string $path, string $contents, array $options = []): string
    {
        if (static::isCloud()) {
            $defaults = [
                'CacheControl' => 'public, max-age=31536000, immutable',
            ];

            // ContentType explícito pra WebP (alguns CDNs não detectam)
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $contentTypes = [
                'webp' => 'image/webp', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png'  => 'image/png',  'gif' => 'image/gif',  'ogg'  => 'audio/ogg',
                'mp3'  => 'audio/mpeg', 'mp4' => 'video/mp4',  'pdf'  => 'application/pdf',
            ];
            if (isset($contentTypes[$ext])) {
                $defaults['ContentType'] = $contentTypes[$ext];
            }

            $options = array_merge($defaults, $options);
        }

        static::disk()->put($path, $contents, $options);
        return $path;
    }

    /**
     * Salva um arquivo uploaded e retorna o path relativo.
     */
    public static function store(\Illuminate\Http\UploadedFile $file, string $directory): string
    {
        return $file->store($directory, static::diskName());
    }

    /**
     * Lê o conteúdo de um arquivo.
     */
    public static function get(string $path): ?string
    {
        return static::disk()->get($path);
    }

    /**
     * Deleta um arquivo.
     */
    public static function delete(string $path): bool
    {
        return static::disk()->delete($path);
    }

    /**
     * Retorna a URL pública de um path.
     *
     * - Disco local (public): retorna /storage/{path} (relativo)
     * - Disco R2/S3: retorna URL absoluta do bucket (https://...)
     */
    public static function url(string $path): string
    {
        if (static::diskName() === 'public') {
            return '/storage/' . $path;
        }

        return static::disk()->url($path);
    }

    /**
     * Verifica se é disco cloud (R2/S3).
     */
    public static function isCloud(): bool
    {
        return !in_array(static::diskName(), ['local', 'public']);
    }

    /**
     * Baixa um arquivo do cloud pra um path local temporário.
     * Necessário pra operações que precisam de acesso ao filesystem
     * (ex: ffmpeg). Retorna o path absoluto local.
     */
    public static function downloadToTemp(string $path): ?string
    {
        $contents = static::get($path);
        if (!$contents) return null;

        $tmpPath = sys_get_temp_dir() . '/' . basename($path);
        file_put_contents($tmpPath, $contents);

        return $tmpPath;
    }
}
