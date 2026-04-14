<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Comprime e gera thumbnails de imagens usando Intervention Image v3 (driver GD).
 *
 * - Otimiza: redimensiona pra max 1920px de largura + converte pra WebP qualidade 80%
 * - Thumbnail: 300px de largura, WebP qualidade 70%
 * - Stickers e GIFs passam direto (não otimiza animações)
 */
class ImageOptimizer
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Otimiza uma imagem: redimensiona + converte pra WebP.
     *
     * @return array{optimized: string, thumbnail: string}
     */
    public function optimize(string $contents, int $maxWidth = 1920, int $quality = 80): array
    {
        $image = $this->manager->read($contents);

        // Não ampliar imagens menores que maxWidth
        if ($image->width() > $maxWidth) {
            $image->scaleDown(width: $maxWidth);
        }

        $optimized = $image->toWebp($quality)->toString();

        // Thumbnail
        $thumb = $this->manager->read($contents);
        $thumb->scaleDown(width: 300);
        $thumbnail = $thumb->toWebp(70)->toString();

        return [
            'optimized' => $optimized,
            'thumbnail' => $thumbnail,
        ];
    }

    /**
     * Gera apenas o thumbnail (usado pra vídeos com jpegThumbnail do WhatsApp).
     */
    public function thumbnailOnly(string $contents, int $width = 300, int $quality = 70): string
    {
        $image = $this->manager->read($contents);
        $image->scaleDown(width: $width);
        return $image->toWebp($quality)->toString();
    }

    /**
     * Verifica se o MIME é otimizável (imagens estáticas).
     * GIFs e stickers animados não devem ser comprimidos.
     */
    public function shouldOptimize(string $mime, string $type = 'image'): bool
    {
        if ($type === 'sticker') return false;
        if (str_contains($mime, 'gif')) return false;

        return in_array($mime, [
            'image/jpeg', 'image/jpg', 'image/png',
            'image/webp', 'image/heic', 'image/heif',
        ], true);
    }

    /**
     * Tenta otimizar com fallback seguro — se falhar, retorna null e loga.
     * Nunca quebra o fluxo principal por causa de otimização.
     */
    public function tryOptimize(string $contents, string $mime, string $type = 'image'): ?array
    {
        if (!$this->shouldOptimize($mime, $type)) {
            return null;
        }

        try {
            return $this->optimize($contents);
        } catch (\Throwable $e) {
            Log::warning('ImageOptimizer: falha ao otimizar', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
