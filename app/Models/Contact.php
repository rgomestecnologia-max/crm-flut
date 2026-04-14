<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use App\Models\CrmCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use Auditable, BelongsToCompany;

    protected $fillable = ['company_id', 'phone', 'chat_lid', 'name', 'email', 'avatar_url', 'notes'];

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function crmCards(): HasMany
    {
        return $this->hasMany(CrmCard::class, 'contact_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->phone;
    }

    public function getAvatarAttribute(): string
    {
        $url = $this->avatar_url;
        if ($url && $url !== 'null' && $url !== '') {
            return $url;
        }

        if ($this->name) {
            // Iniciais do nome em SVG local
            $words    = array_filter(explode(' ', trim($this->name)));
            $initials = collect($words)
                ->take(2)
                ->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))
                ->join('');

            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64">'
                 . '<rect width="64" height="64" rx="32" fill="#0d1a2e"/>'
                 . '<text x="32" y="42" text-anchor="middle" dominant-baseline="auto" '
                 . 'font-family="system-ui,sans-serif" font-size="24" font-weight="700" fill="#b2ff00">'
                 . htmlspecialchars($initials ?: '?')
                 . '</text></svg>';
        } else {
            // Ícone genérico de pessoa para contatos sem nome
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 64 64">'
                 . '<rect width="64" height="64" rx="32" fill="#0d1a2e"/>'
                 . '<circle cx="32" cy="25" r="11" fill="none" stroke="#b2ff00" stroke-width="2.5" opacity="0.7"/>'
                 . '<path d="M12 55c0-11 8.95-20 20-20s20 8.95 20 20" fill="none" stroke="#b2ff00" stroke-width="2.5" stroke-linecap="round" opacity="0.7"/>'
                 . '</svg>';
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
