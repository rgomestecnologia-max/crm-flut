<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'department_id', 'company_id',
        'avatar', 'status', 'is_active', 'modules', 'last_seen_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    /** Cache em memória dos departmentIds para evitar N queries por request. */
    protected ?array $cachedDepartmentIds = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'modules'           => 'array',
            'last_seen_at'      => 'datetime',
        ];
    }

    /**
     * Verifica se o usuário tem acesso a um módulo específico.
     * Admin/supervisor veem tudo. Agentes respeitam a lista de módulos atribuídos.
     * Se modules é null, tem acesso a todos (retrocompat com agentes existentes).
     */
    public function hasModule(string $key): bool
    {
        if ($this->isAdmin() || $this->isSupervisor()) return true;
        if ($this->modules === null) return true; // retrocompat: sem restrição
        return in_array($key, $this->modules, true);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Todos os departamentos aos quais o usuário pertence (principal + extras).
     * O principal (users.department_id) é mantido para retrocompatibilidade
     * em jobs/logs e SEMPRE está sincronizado nesta pivô.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user');
    }

    /**
     * IDs de todos os departamentos do usuário, considerando a pivô.
     * Inclui o principal mesmo que (por algum motivo) não esteja sincronizado.
     * Cacheado em memória durante a request.
     */
    public function departmentIds(): array
    {
        if ($this->cachedDepartmentIds !== null) {
            return $this->cachedDepartmentIds;
        }

        $ids = $this->departments()->pluck('departments.id')->all();

        if ($this->department_id && !in_array($this->department_id, $ids, true)) {
            $ids[] = $this->department_id;
        }

        return $this->cachedDepartmentIds = array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Verifica se o usuário pertence a um departamento específico.
     */
    public function belongsToDepartment(int $departmentId): bool
    {
        return in_array($departmentId, $this->departmentIds(), true);
    }

    public function assignedConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupervisor(): bool
    {
        return $this->role === 'supervisor';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function canManageAll(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Admin ou supervisor podem gerenciar recursos da empresa
     * (departamentos, agentes, pipelines, chatbot, IA, automação).
     */
    public function canManageCompany(): bool
    {
        return in_array($this->role, ['admin', 'supervisor']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtra usuários que pertencem a um departamento (principal OU adicional).
     */
    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->where('department_id', $departmentId)
              ->orWhereHas('departments', fn($d) => $d->where('departments.id', $departmentId));
        });
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            $url = \App\Services\MediaStorage::url($this->avatar);
            $isLocal = str_starts_with($url, '/');
            $r2Url   = config('filesystems.disks.r2.url');
            $isR2    = $r2Url && str_starts_with($url, $r2Url);
            if ($isLocal || $isR2) {
                return $url;
            }
        }

        $words = array_filter(explode(' ', trim($this->name)));
        $initials = collect($words)->take(2)->map(fn($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64">'
             . '<rect width="64" height="64" rx="32" fill="#14B8A6"/>'
             . '<text x="32" y="42" text-anchor="middle" font-family="system-ui,sans-serif" font-size="24" font-weight="700" fill="#fff">'
             . htmlspecialchars($initials ?: '?')
             . '</text></svg>';
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Verifica se o usuário está online (ativo nos últimos 5 minutos).
     */
    public function isOnline(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->greaterThan(now()->subMinutes(5));
    }

    /**
     * Atualiza o heartbeat (no máximo 1x por minuto para não sobrecarregar).
     */
    public function touchLastSeen(): void
    {
        if (!$this->last_seen_at || $this->last_seen_at->lt(now()->subMinute())) {
            $this->timestamps = false;
            $this->update(['last_seen_at' => now(), 'status' => 'online']);
            $this->timestamps = true;
        }
    }
}
