<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'conversation_id', 'sender_type', 'sender_id', 'sender_name', 'sender_phone',
        'content', 'type', 'media_url', 'media_filename', 'media_duration',
        'zapi_message_id', 'delivery_status', 'is_read', 'reactions',
    ];

    protected $casts = [
        'is_read'   => 'boolean',
        'reactions' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function isFromContact(): bool { return $this->sender_type === 'contact'; }
    public function isFromAgent(): bool   { return $this->sender_type === 'agent'; }
    public function isSystem(): bool      { return $this->sender_type === 'system'; }
    public function isMedia(): bool       { return in_array($this->type, ['image', 'audio', 'document', 'video', 'sticker']); }

    /**
     * URL do thumbnail da mídia (convenção de nome: original_thumb.ext).
     * Retorna null se não for imagem/vídeo ou se não tiver media_url.
     */
    public function getMediaThumbUrlAttribute(): ?string
    {
        if (!$this->media_url || !in_array($this->type, ['image', 'video'])) {
            return null;
        }

        $url    = $this->media_url;
        $dotPos = strrpos($url, '.');
        if ($dotPos === false) return null;

        // Vídeos: thumbnail é sempre .webp (gerado pelo ImageOptimizer)
        // Imagens: thumbnail mantém a mesma extensão (.webp se otimizado)
        if ($this->type === 'video') {
            return substr($url, 0, $dotPos) . '_thumb.webp';
        }

        return substr($url, 0, $dotPos) . '_thumb' . substr($url, $dotPos);
    }
}
