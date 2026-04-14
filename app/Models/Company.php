<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'color', 'logo', 'is_active', 'modules',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'modules'   => 'array',
        ];
    }

    /**
     * Catálogo de módulos disponíveis, organizados por seção.
     * Key é o identificador interno, label é o texto pro admin.
     */
    public const AVAILABLE_MODULES = [
        'principal' => [
            'dashboard' => 'Dashboard',
            'chat'      => 'Atendimento',
            'crm'       => 'CRM',
        ],
        'gestao' => [
            'admin.crm'        => 'Pipelines CRM',
            'admin.departments'=> 'Departamentos',
            'admin.agents'     => 'Agentes',
            'admin.chatbot'    => 'Chatbot',
            'admin.ia'         => 'IA de Atendimento',
            'admin.automation' => 'Automação',
            'admin.audit'      => 'Auditoria',
        ],
    ];

    /**
     * Retorna todas as keys de todos os módulos disponíveis.
     */
    public static function allModuleKeys(): array
    {
        $keys = [];
        foreach (self::AVAILABLE_MODULES as $section) {
            $keys = array_merge($keys, array_keys($section));
        }
        return $keys;
    }

    /**
     * Verifica se a empresa tem acesso a um módulo específico.
     */
    public function hasModule(string $key): bool
    {
        $modules = $this->modules ?? [];
        return in_array($key, $modules, true);
    }

    /**
     * Gera o slug automaticamente a partir do nome quando não informado.
     */
    protected static function booted(): void
    {
        static::creating(function (Company $company) {
            if (empty($company->slug) && !empty($company->name)) {
                $company->slug = static::makeUniqueSlug($company->name);
            }
        });
    }

    public static function makeUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        if ($base === '') {
            $base = 'empresa';
        }

        $slug = $base;
        $i    = 1;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? \App\Services\MediaStorage::url($this->logo) : null;
    }
}
