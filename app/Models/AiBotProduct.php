<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AiBotProduct extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = [
        'company_id', 'type', 'name', 'description', 'photo_path',
        'document_path', 'document_content',
        'show_price', 'price', 'is_active',
    ];

    protected $casts = [
        'show_price' => 'boolean',
        'is_active'  => 'boolean',
        'price'      => 'decimal:2',
    ];

    public function getTypeLabel(): string
    {
        return $this->type === 'produto' ? 'Produto' : 'Serviço';
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photo_path ? \App\Services\MediaStorage::url($this->photo_path) : null;
    }

    public function getPhotoAbsoluteUrl(): ?string
    {
        if (!$this->photo_path) return null;
        $url = \App\Services\MediaStorage::url($this->photo_path);
        // Se já é absoluta (cloud), retorna direto; se relativa (local), faz absoluta
        return str_starts_with($url, 'http') ? $url : url($url);
    }

    public function getPriceFormatted(): ?string
    {
        if (!$this->show_price || !$this->price) return null;
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }

    public function getDocumentAbsoluteUrl(): ?string
    {
        if (!$this->document_path || $this->document_path === 'text-input') return null;
        $url = \App\Services\MediaStorage::url($this->document_path);
        return str_starts_with($url, 'http') ? $url : url($url);
    }

    /** Descrição compacta para incluir no system prompt da IA */
    public function toPromptLine(): string
    {
        $line = "- [{$this->getTypeLabel()}] {$this->name}";
        if ($this->description) $line .= ": {$this->description}";
        if ($this->show_price && $this->price) $line .= " | Valor: {$this->getPriceFormatted()}";
        if ($this->photo_path) $line .= " | FOTO: " . $this->getPhotoAbsoluteUrl();
        if ($this->document_path && $this->document_path !== 'text-input') $line .= " | PDF: " . $this->getDocumentAbsoluteUrl();
        if ($this->document_content) $line .= "\n  CONTEÚDO DO DOCUMENTO:\n  " . str_replace("\n", "\n  ", $this->document_content);
        return $line;
    }
}
