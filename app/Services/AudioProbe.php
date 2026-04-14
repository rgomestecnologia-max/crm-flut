<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Extrai duração de arquivos de áudio usando ffprobe.
 */
class AudioProbe
{
    /**
     * Retorna a duração em segundos de um conteúdo binário de áudio.
     * Salva em temp, roda ffprobe, limpa.
     */
    public static function duration(string $contents): ?float
    {
        $ffprobe = static::findFfprobe();
        if (!$ffprobe) return null;

        $tmp = tempnam(sys_get_temp_dir(), 'audio_') . '.ogg';
        file_put_contents($tmp, $contents);

        try {
            $output = [];
            $code   = 0;
            exec("{$ffprobe} -v quiet -show_entries format=duration -of csv=p=0 " . escapeshellarg($tmp) . " 2>/dev/null", $output, $code);

            if ($code === 0 && !empty($output[0])) {
                $duration = (float) trim($output[0]);
                return $duration > 0 ? $duration : null;
            }
        } catch (\Throwable $e) {
            Log::warning('AudioProbe: falha', ['error' => $e->getMessage()]);
        } finally {
            @unlink($tmp);
        }

        return null;
    }

    /**
     * Extrai duração de um arquivo já salvo no MediaStorage (local ou R2).
     */
    public static function durationFromStorage(string $path): ?float
    {
        $contents = MediaStorage::get($path);
        if (!$contents) return null;
        return static::duration($contents);
    }

    protected static function findFfprobe(): ?string
    {
        foreach (['/opt/homebrew/bin/ffprobe', '/usr/local/bin/ffprobe', '/usr/bin/ffprobe'] as $p) {
            if (file_exists($p)) return $p;
        }
        return null;
    }
}
